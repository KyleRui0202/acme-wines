<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class OrdersIndexWithNoFilterTest extends TestCase
{
    // Wrap every test case in a database transaction
    // to reset the database after each test
    use DatabaseTransactions;

    /**
     * A test for the endpoint "orders/{id}" when
     * the corresponding order exists.
     *
     * @test
     * @dataProvider numOfOrdersAndExistingOrderIdProvider
     * @param int $numOfOrders
     * @param int $targetId
     * @return void
     */
    public function a_request_with_existing_id_is_sent_to_order_show_endpoint($numOfOrders, $targetId)
    {
        $idOffset = 10000;

        for ($i = 0; $i < $numOfOrders-1; $i++) {
            if ($targetId != $i + $idOffset) {
                $order = factory('App\Order')->create([
                    'id' => $i + $idOffset]);
            }
        }

        $order = factory('App\Order')->create([
                'id' => $targetId]);

        $result = $order->fresh()->toArray();

        $this->get('/orders/'.$targetId)
             ->seeJson($result);
    }

    /*
     * Provide the num of orders and the id of
     * an existing order for testing.
     */
    public function numOfOrdersAndExistingOrderIdProvider()
    {
        return [
            'normal_integer_id' => [1, 50],
            'max_integer_id' => [4, PHP_INT_MAX],
            'min_zero_id' => [7, 0],
        ];
    }

    /**
     * A test for the endpoint "orders/{id}" when
     * the corresponding order does not exist.
     *
     * @test
     * @dataProvider numOfOrdersAndNonExistingOrderIdProvider
     * @param int $numOfOrders
     * @param string $targetId
     * @return void
     */
    public function a_request_with_non_existing_id_is_sent_to_order_show_endpoint($numOfOrders, $targetId)
    {
        $idOffset = 10000;

        for ($i = 0; $i < $numOfOrders-1; $i++) {
            if ($targetId != $i + $idOffset) {
                $order = factory('App\Order')->create([
                    'id' => $i + $idOffset]);
            }
        }

        $this->get('/orders/'.$targetId)
             ->seeJsonStructure(['no_result']);
    }

    /*
     * Provide the num of orders and the id of
     * an existing order for testing.
     */
    public function numOfOrdersAndNonExistingOrderIdProvider()
    {
        return [
            'positive_integer_id' => [1, '50'],
            'max_integer_id' => [4, (string) PHP_INT_MAX],
            'negative_integer_id' => [3, '-1'],
            'float_id' => [7, '4.5'],
            'string_id' => [5, 'ert'],
        ];
    }

}
