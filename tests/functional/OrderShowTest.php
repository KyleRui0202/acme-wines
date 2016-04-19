<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class OrderShowTest extends TestCase
{
    // Wrap every test case in a database transaction
    // to reset the database after each test
    use DatabaseTransactions;

    /**
     * A test for the endpoint "orders/{id}" when
     * the corresponding order exists.
     *
     * @test
     * @dataProvider numOfOrdersProvider
     * @param int $numOfOrders
     * @return void
     */
    public function a_request_with_existing_id_is_sent_to_order_show_endpoint($numOfOrders)
    {
        if ($numOfOrders > 1) {
            $orders = factory('App\Order', $numOfOrders)->create();
        }
        
        // Create and save another "Order" instance with unique "id"
        // as the targte "id" to show
        $targetOrder = factory('App\Order')->create();
        $result = $targetOrder->fresh()->toArray();

        $this->get('/orders/'.$targetOrder->id)
             ->seeJson($result);
    }

    /**
     * A test for the endpoint "orders/{id}" when
     * the corresponding order to the "id" does not exist.
     *
     * @test
     * @dataProvider numOfOrdersProvider
     * @param int $numOfOrders
     * @return void
     */
    public function a_request_with_non_existing_id_is_sent_to_order_show_endpoint($numOfOrders)
    {
        $orders = factory('App\Order', $numOfOrders)->create();
        
        // Create another "Order" instance with unique "id" but does not save it
        // into the database so that we can use its "id" as the non-existing "id" 
        $targetOrder = factory('App\Order')->make();

        $this->get('/orders/'.$targetOrder->id)
             ->seeJsonStructure(['no_result']);
    }

    /*
     * Provide the total num of orders for testing.
     */
    public function numOfOrdersProvider()
    {
        return [
            [1], [7], [12],
        ];
    }
    
    /**
     * A test for the endpoint "orders/{id}" when
     * the "id" is not a valid non-negative integer.
     *
     * @test
     * @dataProvider invalidIdProvider
     * @param string invalidId
     * @return void
     */
    public function a_request_with_invalid_id_is_sent_to_order_show_endpoint($invalidId)
    {
        $numOfOrders = 10;
        $orders = factory('App\Order', $numOfOrders)->create();

        $this->get('/orders/'.$invalidId)
             ->seeJsonStructure(['no_result']);
    }

    /*
     * Provide the invalid form of "id" (not
     * a non-negative integer) for testing.
     */
    public function invalidIdProvider()
    {
        return [
            'negative_integer_id' => ['-2'],
            'float_id' => ['4.5'],
            'non_numeric_id' => ['ert'],
        ];
    }

}
