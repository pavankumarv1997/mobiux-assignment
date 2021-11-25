<?php
  
  if (($open = fopen("sales-data.csv", "r")) !== FALSE) 
  {
    while (($data = fgetcsv($open, 1000, ",")) !== FALSE) 
    {        
      $dataSet[] = $data; 
    }
  
    fclose($open);
  }
  echo "<pre>";
  $response = [];
  $totalSales = getTotalSales($dataSet);
  $monthWiseSalesTotal = getMonthWiseSalesTotal($dataSet);
  $mostPopularItems = getMostPopularAndRevenueItems($dataSet,'quantity');
  $mostRevenueItemsInMonth = getMostPopularAndRevenueItems($dataSet,'total price');
  $popularItemsObj = json_decode($mostPopularItems);
  $popularItemsRecords = (array) $popularItemsObj;
  $popularItems = array_unique(array_values($popularItemsRecords));
  $minMaxandAvgOrderPerMonth = getMinMaxandAvgOrderPerMonth($dataSet,$popularItems);

  $response['totalSales'] = $totalSales;
  $response['monthWiseSalesTotal'] = json_decode($monthWiseSalesTotal);
  $response['mostPopularItems'] = $popularItemsRecords;
  $response['mostRevenueItemsInMonth'] = json_decode($mostRevenueItemsInMonth);
  $response['minMaxandAvgOrdersPerMonth'] = json_decode($minMaxandAvgOrderPerMonth);
  echo json_encode($response);
  echo "</pre>";

  function getSearchIndex($type,$headerRow){  
  	switch ($type) {
  		case 'date':
  			$searchIndex = array_search('date', $headerRow);
  			return $searchIndex;
  			break;
  		case 'sku':
  			$searchIndex = array_search('sku', $headerRow);
  			return $searchIndex;
  			break;
  		case 'unit price':
  			$searchIndex = array_search('unit price', $headerRow);
  			return $searchIndex;
  			break;
  		case 'quantity':
  			$searchIndex = array_search('quantity', $headerRow);
  			return $searchIndex;
  			break;
  		case 'total price':
  			$searchIndex = array_search('total price', $headerRow);
  			return $searchIndex;
  			break;
  		default:
  			break;
  	}
  }
  function getTotalSales($dataSet){
  	// read first row, convert row values to lowercase and find quantity column position
  	$headerRow = array_map('strtolower', $dataSet[0]);
  	$searchIndex = getSearchIndex('total price',$headerRow);
  	$totalSales = 0;
  	foreach ($dataSet as $salesData) {
  		$totalSales = ((int)$totalSales + (int)$salesData[$searchIndex]);  		
  	}
  	return $totalSales;
  }

  function getUniqueDateMonthYear($dataSet,$searchIndex,$type){
  	foreach ($dataSet as $salesData) {
  		$salesDate[] =  ($salesData[$searchIndex]);	  		
  	}
	array_shift($salesDate);
	if ($type == "month"){
		$uniqueMonths = array_unique(array_map(function($date) {
	    	return DateTime::createFromFormat('Y-m-d', $date)->format("m");
		}, $salesDate));
	}elseif ($type == "year") {
		$uniqueMonths = array_unique(array_map(function($date) {
	    	return DateTime::createFromFormat('Y-m-d', $date)->format("y");
		}, $salesDate));
	}elseif ($type == "day"){
		$uniqueMonths = array_unique(array_map(function($date) {
	    	return DateTime::createFromFormat('Y-m-d', $date)->format("d");
		}, $salesDate));
	}
	
	return $uniqueMonths;
  }

  function getMonthWiseSalesTotal($dataSet){
  	// read csv , get index, get unique month , get sales total
  	$headerRow = array_map('strtolower', $dataSet[0]);
  	$searchIndex = getSearchIndex('date',$headerRow);
  	$uniqueDates = getUniqueDateMonthYear($dataSet,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet,$searchIndex,"year");
	$result = [];
	$labels = array();
	$values = array();	
	foreach ($uniqueMonths as $key => $month) {
	    $from_date = '20'.current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
	    $to_date = '20'.end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
	    foreach($dataSet as $salesItems){
	        if(strtotime($salesItems[0]) >= strtotime($from_date) AND strtotime($salesItems[0]) <= strtotime($to_date)){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems[4];
	        }
	    }
	    $result['20'.current($uniqueYears).'-'.$month] = array_sum($values);
	    $values = [];
	   
	}
	return json_encode($result);
  }

  function getMostPopularAndRevenueItems($dataSet,$searchIndexColumn){
  	// month wise data , sum of quantity sold per day 
  	$headerRow = array_map('strtolower', $dataSet[0]);
  	$searchIndex = getSearchIndex('date',$headerRow);
  	$quantitySearchIndex = getSearchIndex($searchIndexColumn,$headerRow);
  	$uniqueDates = getUniqueDateMonthYear($dataSet,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet,$searchIndex,"year");
	$result = [];
	$labels = array();
	$values = array();	
	$quantity = [];
	$productList = [];
	$uniqueDays = [];
	foreach ($uniqueMonths as $key => $month) {
	    $from_date = '20'.current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
	    $to_date = '20'.end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
	    foreach($dataSet as $salesItems){
	    	// print_r($salesItems);
	        if(strtotime($salesItems[0]) >= strtotime($from_date) AND strtotime($salesItems[0]) <= strtotime($to_date)){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems;
	            $quantity[] = $salesItems[$quantitySearchIndex];
	            $productList[] = $salesItems[1];
	            $uniqueDays[] = $salesItems[0];
	        }
	    }
	    $dayWiseRecords = $values;
	    $maxrowcount =  count($dayWiseRecords)-1;
	    $initial = 0;
	    $qtyArray = [];	  
	    $uniqueproducts = array_unique($productList);
	    $uniquedays = array_unique($uniqueDays);  
	   	foreach ($dayWiseRecords as $key => $dayWiseRecord) {

	    	// get the quantities sold per day for each product

	    	foreach ($uniqueproducts as $uniqueproduct) {
	    		if($dayWiseRecord[0] == $uniqueDays[$initial] && $dayWiseRecord[1] == $uniqueproduct){
	    			
	    			if(array_key_exists($uniqueproduct, $qtyArray)){
	    				$value = $qtyArray[$uniqueproduct];
	    				$qtyArray[$uniqueproduct] = $value + $dayWiseRecord[$quantitySearchIndex];

	    			}else{
	    				$qtyArray[$uniqueproduct] = $dayWiseRecord[$quantitySearchIndex];
	    			}		    				
    			}	
	    	}
	    	$initial++;
	    }
	    $max = max(array_values($qtyArray));
	    $key = array_search($max, $qtyArray);
	    $result['20'.current($uniqueYears).'-'.$month] = $key;
	}

	return json_encode($result);

  }




  function getMinMaxandAvgOrderPerMonth($dataSet,$mostPopularItem){
  	$headerRow = array_map('strtolower', $dataSet[0]);
  	$searchIndex = getSearchIndex('date',$headerRow);
  	$quantitySearchIndex = getSearchIndex('quantity',$headerRow);
  	$uniqueDates = getUniqueDateMonthYear($dataSet,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet,$searchIndex,"year");
	$result = [];
	$labels = array();
	$values = array();	
	$quantity = [];
	$productList = [];
	$uniqueDays = [];
	$daysSales = [];
	foreach ($uniqueMonths as $key => $month) {
	    $from_date = '20'.current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
	    $to_date = '20'.end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
	    foreach($dataSet as $salesItems){
	        if(strtotime($salesItems[0]) >= strtotime($from_date) AND strtotime($salesItems[0]) <= strtotime($to_date) AND $salesItems[1] == $mostPopularItem[0]){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems;
	            $quantity[] = $salesItems[$quantitySearchIndex];
	            $productList[] = $salesItems[1];
	            $uniqueDays[] = $salesItems[0];
	        }
	    }
	   foreach ($values as $valueNew) {
	   		if ($month == date('m', strtotime($valueNew[0]))){
	   			if(in_array($valueNew[0], array_keys($daysSales)) ){		
	    			$daysSales[$valueNew[0]] += $valueNew[$quantitySearchIndex];  
		    	}else{
		    		$daysSales[$valueNew[0]] =  $valueNew[$quantitySearchIndex];
		    	}
	   		}
	    	
	    }
	   
	    $maximumOrdersPerMonth = (int)max($daysSales);
	    $minimumOrdersPerMonth = (int)min($daysSales);
	    $averageOrdersPerMonth = (array_sum($daysSales)/cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears)));
	  
	    $daysSales = [];
	  
	    $result['20'.current($uniqueYears).'-'.$month]['minimumOrdersPerMonth'] = $minimumOrdersPerMonth;
	    $result['20'.current($uniqueYears).'-'.$month]['maximumOrdersPerMonth'] = $maximumOrdersPerMonth;
	    $result['20'.current($uniqueYears).'-'.$month]['averageOrdersPerMonth'] = number_format((float)$averageOrdersPerMonth, 2, '.', '');
	}

	return json_encode($result);
  }		


?>

<!DOCTYPE html>
<html>
<head>
<title>Mobiux Assignment</title>
</head>
<body >
    <section >
        <p>Total sales of the store : <?php echo $totalSales; ?> </p>
    </section>
    <section>
        <p>Month wise sales totals</p>
        <?php print_r($monthWiseSalesTotal);?>
    </section>
    <section>
    	<p>Most popular item (most quantity sold) in each month.
    		<?php print_r($mostPopularItems);?>
    	</p>
    </section>
    <section>
    	<p>Items generating most revenue in each month</p>
    	<?php print_r($mostRevenueItemsInMonth);?>
    </section>
    <section>
    	<p>For the most popular item, find the min, max and average number of orders each month.</p>
    	<?php print_r($minMaxandAvgOrderPerMonth);?>
    </section>

</body>
</html>

