<?php




  if (($open = fopen("sales-data.csv", "r")) !== FALSE) 
  {
    while (($data = fgetcsv($open, 0, ",")) !== FALSE) 
    {        
      $dataSet[] = $data; 
    }
  
    fclose($open);
  }
  // echo "<pre>";

  Global $from_date_p;

  if(isset($_POST['submit'])) {
      $from_date_p = $_POST['from_date'];
      $to_date_p =  $_POST['to_date'];
      $filteredData = array();
      foreach($dataSet as $salesItems){
	        if(strtotime($salesItems[0]) >= strtotime($from_date_p) AND strtotime($salesItems[0]) <= strtotime($to_date_p)){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems[4];
	            $filteredData[] = $salesItems;
	        }

	   }

	  if (count($filteredData) > 0 && isset($filteredData)){
	  	$monthWiseSalesTotal = getMonthWiseSalesTotal($filteredData);
	  	$mostPopularItems = getMostPopularAndRevenueItems($filteredData,'quantity');
	  	$mostRevenueItemsInMonth = $mostPopularItems['mostRevenueItemPerMonth'];
  		$popularItems = array_unique(array_values($mostPopularItems['mostPopularItemPerMonth']));
  		$minMaxandAvgOrderPerMonth = getMinMaxandAvgOrderPerMonth($filteredData,$popularItems);
	  	
	  	 ?>

	  	<section>
	        <p>Month wise sales totals</p>
	        <table border="1">
	    		<th>Year - Month</th>
	    		<th>Sales</th>    	
	    	<?php foreach (json_decode($monthWiseSalesTotal) as $key =>$value) {?>
	    		<tr>
	    			<td><?php echo $key ?></td>
	    			<td><?php echo $value ?></td>
	    		</tr>    		
	    	<?php }
	    	?>
	    	</table>
	    </section>	 
	    <section>
	    	<p>Most popular item (most quantity sold) in each month.
	    		<?php foreach ($mostPopularItems as $key => $mostPopularItemRecord) { ?>
	    			<h1><?php echo $key ?></h1>
	    			<table border="1">
			    		<th>Year - Month</th>
			    		<th>Product Name</th>  
		    			<?php foreach ($mostPopularItemRecord as $key => $value) {?>
		    				<tr>
				    			<td><?php echo $key ?></td>
				    			<td><?php echo $value ?></td>
				    		</tr>     				
		    			<?php } ?>
		    		</table>
	    	    <?php }?>

	    	</p>
	    </section>  
 	    <section>
	    	<p>For the most popular item, find the min, max and average number of orders each month.</p>
	    	<table border="1">
	    		<th>Year - Month</th>
	    		<th>MinOrderPerMonth</th>  
	    		<th>MaxOrderPerMonth</th>  
	    		<th>AvgOrderPerMonth</th>  
	    	<?php foreach (json_decode($minMaxandAvgOrderPerMonth) as $key => $value) {?>
	    		<tr>
	    			<td><?php echo $key ?></td>
	    			<?php foreach ($value as $key => $value1) {?>
	    				<td><?php echo $value1 ?></td>
	    			<?php } ?>
	    		</tr>
	    	<?php } ?>
	    </section>	
	  <?php }else{
	  	echo "Sorry No records found";
	  }	   
    // unset($_POST);
    // header("Location: ".$_SERVER['PHP_SELF']);
    exit;
  }





  $response = [];
  $totalSales = getTotalSales($dataSet);
  $monthWiseSalesTotal = getMonthWiseSalesTotal($dataSet);
  $mostPopularItems = getMostPopularAndRevenueItems($dataSet,'quantity');
  $mostRevenueItemsInMonth = $mostPopularItems['mostRevenueItemPerMonth'];
  $popularItems = array_unique(array_values($mostPopularItems['mostPopularItemPerMonth']));
  $minMaxandAvgOrderPerMonth = getMinMaxandAvgOrderPerMonth($dataSet,$popularItems);
  $response['totalSales'] = $totalSales;
  $response['monthWiseSalesTotal'] = json_decode($monthWiseSalesTotal);
  $response['mostPopularItems'] = $mostPopularItems['mostPopularItemPerMonth'];
  $response['mostRevenueItemsInMonth'] = $mostRevenueItemsInMonth;
  $response['minMaxandAvgOrdersPerMonth'] = json_decode($minMaxandAvgOrderPerMonth);
  // echo json_encode($response);
  // echo "</pre>";

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

  function getUniqueDateMonthYear($dataSet1,$searchIndex,$type){
  	foreach ($dataSet1 as $salesData) {
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

  function getMonthWiseSalesTotal($dataSet1){
  	// read csv , get index, get unique month , get sales total
  	$headerRow = array_map('strtolower', $dataSet1[0]);
  	$searchIndex = getSearchIndex('date',$headerRow);
  	$uniqueDates = getUniqueDateMonthYear($dataSet1,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet1,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet1,$searchIndex,"year");
	
	$result = [];
	$labels = array();
	$values = array();	
	foreach ($uniqueMonths as $key => $month) {
		
		$from_date = '20'.current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
    	$to_date = '20'.end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
		    
	    foreach($dataSet1 as $salesItems){
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

  function getMostPopularAndRevenueItems($dataSet1,$searchIndexColumn){
  	// month wise data , sum of quantity sold per day 
  	$headerRow = array_map('strtolower', $dataSet1[0]);

  	$searchIndex = getSearchIndex('date',$headerRow);
  	$quantitySearchIndex = getSearchIndex($searchIndexColumn,$headerRow);
  	$productSearchIndex =  getSearchIndex($searchIndexColumn,$headerRow);
  	$priceSearchIndex = getSearchIndex('total price',$headerRow);
  	$uniqueDates = getUniqueDateMonthYear($dataSet1,$searchIndex,"day");
	$uniqueMonths = getUniqueDateMonthYear($dataSet1,$searchIndex,"month");	
	$uniqueYears = getUniqueDateMonthYear($dataSet1,$searchIndex,"year");

	$products = array_column($dataSet1, 1);
	$productUList = array_unique($products);
	$productsList = array_slice($productUList,1);
	$result = [];
	$labels = array();
	$values = array();	
	$productList = [];
	$monthWiseQtyData = [];
	$monthWiseSalesData = [];

	foreach ($uniqueMonths as $key => $month) {
	    $from_date = '20'.current($uniqueYears).'-'.$month.'-'.current($uniqueDates);
	    $to_date = '20'.end($uniqueYears).'-'.$month.'-'.cal_days_in_month(CAL_GREGORIAN,$month,end($uniqueYears));
   		foreach($dataSet1 as $salesItems){
   			if(strtotime($salesItems[0]) >= strtotime($from_date) AND strtotime($salesItems[0]) <= strtotime($to_date)){ 
	            $labels[] = $salesItems[0];
	            $values[] = $salesItems;
	            // for both most popular and revenue item
	            if(isset($monthWiseQtyData[$month][$salesItems[1]])){
	            	$monthWiseQtyData[$month][$salesItems[1]] += (int)$salesItems[$quantitySearchIndex];
	            }else{
	            	$monthWiseQtyData[$month][$salesItems[1]] = (int)$salesItems[$quantitySearchIndex];
	            }
	            if(isset($monthWiseSalesData[$month][$salesItems[1]])){
	            	$monthWiseSalesData[$month][$salesItems[1]] += (int)$salesItems[$priceSearchIndex];
	            }else{
	            	$monthWiseSalesData[$month][$salesItems[1]] = (int)$salesItems[$priceSearchIndex];
	            }
	        }	   			
	    }		 
	   	$salesMax = array_sum($monthWiseSalesData[$month]);
	   	$salesMax = max(array_values($monthWiseSalesData[$month]));
	    $mostRevenueItemPerMonth = array_search($salesMax, $monthWiseSalesData[$month]);
	    $max = max(array_values($monthWiseQtyData[$month]));
	    $key = array_search($max, $monthWiseQtyData[$month]);	  
	    $result['mostPopularItemPerMonth']['20'.current($uniqueYears).'-'.$month]  = $key;
	    $result['mostRevenueItemPerMonth']['20'.current($uniqueYears).'-'.$month]  = $mostRevenueItemPerMonth;	    
	}
	return $result;
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
	            if ($month == date('m', strtotime($salesItems[0]))){
		   			if(in_array($salesItems[0], array_keys($daysSales)) ){
		   				
		    			$daysSales[$salesItems[0]] += (int)$salesItems[$quantitySearchIndex];  
			    	}else{
			    		$daysSales[$salesItems[0]] =  (int)$salesItems[$quantitySearchIndex];
			    	}
		   		}
	        }
	    }  
	    // print_r($daysSales);
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

