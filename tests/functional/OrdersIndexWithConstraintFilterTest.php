<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class OrdersIndexWithConstraintFilterTest extends TestCase
{
    // Wrap every test case in a database transaction
    // to reset the database after each test
    use DatabaseTransactions;

    /**
     * A test for the endpoint "orders/" with the filter "valid".
     *
     * @test
     * @dataProvider numOfTotalAndInvalidOrdersProvider
     * @param int $numOfOrders
     * @return void
     */
    public function a_request_with_filter_valid_is_sent_to_orders_index_endpoint($numOfOrders, $numOfInvalidOrders)
    {
        $validResults = [];
        $invalidResults = [];

        for ($i = 0; $i < $numOfOrders; $i++) {
            $isValid = ($i < $numOfInvalidOrders) ? false : true;

            $order = factory('App\Order')->create([
                'valid' => $isValid]);

            if ($isValid === true) {
                $validResults[] = $order->fresh()->toArray();
            }
            else {
                $invalidResults[] = $order->fresh()->toArray();
            }
        }
        
        // Check "valid=1, true, on and yes"
        $termsOfValid = [1, 'true', 'on', 'yes'];

        foreach ($termsOfValid as $term) {
            $this->get('/orders?valid='.$term)
                ->seeJson(['constraint' => ['valid' => true]])
                ->seeJson(['num_of_orders' => ($numOfOrders - $numOfInvalidOrders)])
                ->seeJson(['results' => $validResults]);
        }

        // Check "valid=0, false, off no and ''"
        $termsOfInvalid = [0, 'false', 'off', 'no', ''];

        foreach ($termsOfInvalid as $term) {
            $this->get('/orders?valid='.$term)
                ->seeJson(['constraint' => ['valid' => false]])
                ->seeJson(['num_of_orders' => $numOfInvalidOrders])
                ->seeJson(['results' => $invalidResults]);
        }
        
    }

    /*
     * Provide the number of total orders and
     * number of invalid orders among for testing.
     */
    public function numOfTotalAndInvalidOrdersProvider()
    {
        return [
            'all_orders_are_invalid' => [3, 3],
            'all_orders_are_valid' => [5, 0],
            'a_single_invalid_order' => [7, 1],
            'two_valid_orders' => [10, 8],
        ];
    }

    /**
     * A test for the endpoint "orders/" with the filter "limit".
     *
     * @test
     * @dataProvider numOfTotalOrdersAndLimitFilterValueProvider
     * @param int $numOfOrders
     * @param string $limitValue
     * @return void
     */
    public function a_request_with_filter_limit_is_sent_to_orders_index_endpoint($numOfOrders, $limitValue)
    {
        $results = [];
        $parsedLimit = filter_var($limitValue, FILTER_VALIDATE_INT);

        for ($i = 0; $i < $numOfOrders; $i++) {
            $order = factory('App\Order')->create();

            $results[$order->id] = $order->fresh()->toArray();
        }

        if ($parsedLimit !== false && $parsedLimit > 0) {
            ksort($results);
            $results = array_slice($results, 0, $parsedLimit);

            $this->get('/orders?limit='.$limitValue)
                ->seeJson(['constraint' => ['limit' => $parsedLimit]])
                ->seeJson(['results' => array_values($results)]);
        }
        else {
            $this->get('/orders?limit='.$limitValue)
                ->seeJson(['effect_filters' => []])
                ->seeJson(['results' => array_values($results)]);
        }
        
    }

    /*
     * Provide the numbers of total orders and
     * the value of "limit" filter among for testing.
     */
    public function numOfTotalOrdersAndLimitFilterValueProvider()
    {
        return [
            'string_value' => [6, 'asd'],
            'negative_value' => [4, '-3'],
            'zero_value' => [3, '0'],
            'positive_integer_value_smaller_than_num_of_orders' => [5, '3'],
            'same_value_as_num_of_orders' => [7, '7'],
            'greater_value_than_num_of_orders' => [10, '12'],
        ];
    }

    /**
     * A test for the endpoint "orders/" with the filter "offset".
     *
     * @test
     * @dataProvider numOfTotalOrdersAndOffsetFilterValueProvider
     * @param int $numOfOrders
     * @param string $offsetValue
     * @return void
     */
    public function a_request_with_filter_offset_is_sent_to_orders_index_endpoint($numOfOrders, $offsetValue)
    {
        $results = [];
        $parsedOffset = filter_var($offsetValue, FILTER_VALIDATE_INT);

        for ($i = 0; $i < $numOfOrders; $i++) {
            $order = factory('App\Order')->create();

            $results[$order->id] = $order->fresh()->toArray();
        }
        
        if ($parsedOffset !== false && $parsedOffset > 0) {
            ksort($results);
            $results = array_slice($results, $parsedOffset);

            $this->get('/orders?offset='.$offsetValue)
                ->seeJson(['constraint' => ['offset' => $parsedOffset]])
                ->seeJson(['results' => array_values($results)]);
        }
        else {
            $this->get('/orders?offset='.$offsetValue)
                ->seeJson(['effect_filters' => []])
                ->seeJson(['results' => array_values($results)]);
        }
        
    }

    /*
     * Provide the numbers of total orders and
     * the value of "offset" filter among for testing.
     */
    public function numOfTotalOrdersAndOffsetFilterValueProvider()
    {
        return [
            'string_value' => [6, 'asd'],
            'negative_value' => [4, '-3'],
            'zero_value' => [3, '0'],
            'positive_integer_value_smaller_than_num_of_orders' => [5, '3'],
            'same_value_as_num_of_orders' => [7, '7'],
            'greater_value_than_num_of_orders' => [10, '12'],
        ];
    }

}
