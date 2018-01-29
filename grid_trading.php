<?php
/*
策略
下降到一个网格买入
上涨到一个网格，把上一个网格卖掉
 */
$cash = 3000;
$grid = [];
$lines = file('fund.txt');
$price_range = get_grid_price_range(2.465, -0.014);
// print_r($price_range);
foreach ($lines as $line_num => $line) {
    list($date, $net_value, $growth_rate) = preg_split('/\s+/', trim($line));
    $prev_grid_price = get_prev_grid_price($grid, count($grid)-1);
    $current_grid_price = get_grid_price($net_value, $growth_rate, $prev_grid_price);
    
    $status = $profit = $grid_price = $operate = '';

    $row = compact('date', 'net_value', 'growth_rate', 'operate', 'grid_price', 'status', 'profit');
    
    // echo $prev_grid_price . "\n";
    if (count($current_grid_price) > 0) {
    	foreach ($current_grid_price as $p) {
    		$row['grid_price'] = $p;
    		$row['operate'] = $growth_rate < 0 ? 'buy' : 'sell';
    		array_push($grid, $row);
    		if ($row['operate'] == 'sell') {
    			update_status($grid);
    		}
    	}
    }else{
    	array_push($grid, $row);
    }
}

$s = array_reduce(
    $grid, 
    function($carry, $item) { 
    	$carry += $item['profit'] ? $item['profit'] : 0;
    	return $carry;
    }, 
    0
);

print_table($grid);
echo "total:" . $s . "\n";


function update_status(&$grid)
{
	global $cash;
	$index = count($grid)-1;
	for ($i=$index-1; $i >=0 ; $i--) { 
		if ($grid[$i]['operate'] == 'buy' && !$grid[$i]['status']) {
			$grid[$i]['status'] = 1;
			$grid[$index]['profit'] = round(($grid[$index]['net_value'] - $grid[$i]['net_value'])/$grid[$i]['net_value']*$cash, 2);
			break;
		}
	}
	
}

function print_table($grid)
{
	foreach ($grid as $row) {
		echo '|' . implode(' | ', array_values($row)) . "|\n";
	}
}
function get_prev_grid_price($grid, $index)
{
	if ($index < 0) {
		return null;
	}
	for ($i=$index; $i >=0 ; $i--) { 
		if (!empty($grid[$i]['grid_price'])) {
			return $grid[$i]['grid_price'];
			break;
		}
	}
}

function get_grid_price_range($begin, $per, $len = 20)
{
	$price_arr = [$begin];
	for ($i=1; $i < $len; $i++) { 
		$price_arr[$i] = round($price_arr[$i-1] * $per + $price_arr[$i-1], 4);
	}
	return $price_arr;
}

function get_grid_price($net_value, $growth_rate, $prev_grid_price = null)
{
	global $price_range;
	$len = count($price_range);
	$prices = [];
	for ($i=0; $i < $len; $i++) { 
		if ($net_value > $price_range[$i]) {
			if ($prev_grid_price) {
				$pre_index = array_search($prev_grid_price, $price_range);
				if ($growth_rate < 0 && $i-1-$pre_index >= 1) {
					for ($j=$pre_index+1; $j <= $i-1; $j++){
						array_push($prices, $price_range[$j]);
					}
				}elseif ($growth_rate > 0 && $pre_index-$i >= 1) {
					for ($j=$pre_index-1; $j >=$i ; $j--){
						array_push($prices, $price_range[$j]);
					}
				}
			}else{
				array_push($prices, $price_range[$i - 1]);
			}
			break;
		}
	}
	return $prices;
}
