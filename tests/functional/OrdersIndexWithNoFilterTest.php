<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class OrdersIndexWithNoFilterTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A test for the endpoint "orders/" with no filter.
     *
     * @test
     * @dataProvider numOfOrdersProvider
     * @param int $numOfOrders
     * @return void
     */
    public function a_request_with_no_filter_is_sent_to_orders_index_endpoint($numOfOrders)
    {
        $results = [];
        $idOffset = 10000;
        for ($i = 0; $i < $numOfOrders; $i++) {
            $order = factory('App\Order')->create([
                'id' => $i + $idOffset]);
            $results[] = $order->fresh()->toArray();
        }
        $this->get('/orders')
             ->seeJson(['effect_filters' => []])
             ->seeJson(['num_of_orders' => $numOfOrders])
             ->seeJson(['results' => $results]);
    }

    /*
     * Provide the num of orders for testing.
     */
    public function numOfOrdersProvider() {
        return [[1], [4], [11]];
    }

}
