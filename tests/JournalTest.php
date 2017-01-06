<?php

use Money\Money;
use Scottlaurent\Accounting\Models\Journal;

use Models\User;
use Models\Account;
use Models\Product;

/**
 * Class JournalTest
 */
class JournalTest extends \Orchestra\Testbench\TestCase
{
	
	/**
	 *
	 */
	public function testJournals()
	{
		
		// create some sample model types that will have journals
		$user = User::create(['name'=>'User X']);
		
		// initialize journals for these models
		$user->initJournal();
		
        // we have created journals
        $this->assertInstanceOf(Journal::class, User::find($user->id)->journal);
        
        // we get money balances for our journals
        $this->assertInstanceOf(Money::class, User::find($user->id)->journal->balance);
        
        // our journals have a zero balance
        $this->assertEquals(0,User::find($user->id)->journal->balance->getAmount());
		
        $user_journal = User::find($user->id)->journal;
		
        // we can credit a journal and get back dollar balances and standard Money balances
        $user_journal->creditDollars(100);
		
        $this->assertEquals(100,$user_journal->getCurrentBalanceInDollars());
		$this->assertEquals(10000,$user_journal->getCurrentBalance()->getAmount());
		
        // we can debit a journal
        $user_journal = User::find($user->id)->journal;
        $user_journal->debitDollars(100.99);
		$this->assertEquals(-0.99,$user_journal->getCurrentBalanceInDollars());
		$this->assertEquals(-99,$user_journal->getCurrentBalance()->getAmount());
		
	}
	
	
	/**
	 *
	 */
	public function testJournalObjectReferences()
	{
		
		/*
		|--------------------------------------------------------------------------
		| setup
		|--------------------------------------------------------------------------
		*/
		
		$user = User::create(['name'=>'User Y']);
		$user->initJournal();
		$user_journal = $user->fresh()->journal;
		
		$account = Account::create(['name'=>'Account X']);
		$account->initJournal();
		$account_journal = Account::find(1)->journal;
		
		$product = Product::create(['name'=>'Product 1','price'=>mt_rand(10000,20000) / 100]);
		$qty_products = mt_rand(2,5);
		
		// credit the account journal for some products that have been purchased.
        $a_transaction = $account_journal->creditDollars($product->price * $qty_products);
        
        // reference the product inside this transaction
        $a_transaction->referencesObject($product);
        
		// debit the user journal for some products that have been purchased.
        $u_transaction = $user_journal->debitDollars($product->price * $qty_products);
        
        // reference the product inside this transaction
        $u_transaction->referencesObject($product);
        
		/*
		|--------------------------------------------------------------------------
		| assertions
		|--------------------------------------------------------------------------
		*/
		
        // make sure that the amount credited is correct...
		$this->assertEquals($product->price * $qty_products,$account_journal->getCurrentBalanceInDollars(),"Product Purchase Income");
		// and also that the referenced product can be retrieved from the transaction
		$this->assertInstanceOf($a_transaction->ref_class,$a_transaction->getReferencedObject());
		$this->assertEquals($a_transaction->getReferencedObject()->fresh(),$product->fresh());
		
		// make sure that the amount debited is correct...
		$this->assertEquals(-1 * $product->price * $qty_products,$user_journal->getCurrentBalanceInDollars(),"Products Purchased");
		// and also that the referenced product can be retrieved from the transaction
		$this->assertInstanceOf($u_transaction->ref_class,$u_transaction->getReferencedObject());
		$this->assertEquals($u_transaction->getReferencedObject()->fresh(),$product->fresh());
	}
	
	
	/**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();
        $this->loadMigrationsFrom([
            '--database' => 'testing',
            '--realpath' => realpath(__DIR__.'/migrations'),
        ]);
    }
    
	/**
	 * Define environment setup.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function getEnvironmentSetUp($app)
	{
		$app['config']->set('database.default', 'testing');
		
	    // Setup default database to use sqlite :memory:
	    $app['config']->set('database.default', 'testbench');
	    $app['config']->set('database.connections.testbench', [
	        'driver'   => 'sqlite',
	        'database' => ':memory:',
	        'prefix'   => '',
	    ]);
	    
	    Eloquent::unguard();
	}

}