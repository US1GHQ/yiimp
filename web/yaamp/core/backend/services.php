<?php

function BackendUpdateServices()
{
//	debuglog(__FUNCTION__);
	
	$table = array(
		0=>'scrypt',
		1=>'sha256',
		2=>'scryptn',
		3=>'x11',
		4=>'x13',
		5=>'keccak',
		6=>'x15',
		7=>'nist5',
		8=>'neoscrypt',
		9=>'lyra2',
		10=>'whirlx',
		11=>'qubit',
		12=>'quark',

		111=>'c11',
	);

	$res = fetch_url('https://www.nicehash.com/api?method=stats.global.current');
	if(!$res) return;

	$a = json_decode($res);
	if(!$a || !isset($a->result)) return;
	
	foreach($a->result->stats as $stat)
	{
		if($stat->price <= 0) continue;
		if(!isset($table[$stat->algo])) continue;
		$algo = $table[$stat->algo];

		$service = getdbosql('db_services', "name='Nicehash' and algo=:algo", array(':algo'=>$algo));
		if(!$service)
		{
			$service = new db_services;
			$service->name = 'Nicehash';
			$service->algo = $algo;
		}
		
		$service->price = $stat->price/1000;
		$service->speed = $stat->speed*1000000000;
		$service->save();

		$list = getdbolist('db_jobs', "percent>0 and algo=:algo and (host='stratum.westhash.com' or host='stratum.nicehash.com')", array(':algo'=>$algo));
		foreach($list as $job)
		{
			$job->price = round($service->price*1000*(100-$job->percent)/100, 2);
			$job->save();
		}
	}
	
	$list = getdbolist('db_renters', "custom_address is not null and custom_server is not null");
	foreach($list as $renter)
	{
		$res = fetch_url("https://$renter->custom_server/api?method=stats.provider&addr=$renter->custom_address");
		if(!$res) continue;
		
		$renter->custom_balance = 0;
		$renter->custom_accept = 0;
		$renter->custom_reject = 0;
			
		$a = json_decode($res);
		foreach($a->result->stats as $stat)
		{
			if(!isset($table[$stat->algo])) continue;
			$algo = $table[$stat->algo];
		
			$renter->custom_balance += $stat->balance;
			$renter->custom_accept += $stat->accepted_speed*1000000000;
			$renter->custom_reject += $stat->rejected_speed*1000000000;
		}

 		$renter->save();
 	}
	
	///////////////////////////////////////////////////////////////////////////

	// renting from nicehash
	if(!YAAMP_PRODUCTION) return;
return;
	
	$apikey = 'c9534a11-0e4e-4d00-be64-a00e34cd927a';
	$apiid = '7215';
	
	$deposit = '1C23KmLeCaQSLLyKVykHEUse1R7jRDv9j9';
	$amount = '0.01';

	$res = fetch_url("https://www.nicehash.com/api?method=balance&id=$apiid&key=$apikey");
	$a = json_decode($res);
	$balance = $a->result->balance_confirmed;
	
	foreach($table as $i=>$algo)
	{
		$nicehash = getdbosql('db_nicehash', "algo=:algo", array(':algo'=>$algo));
		if(!$nicehash)
		{
			$nicehash = new db_nicehash;
			$nicehash->active = false;
			$nicehash->algo = $algo;
		}
		
		if(!$nicehash->active)
		{
			if($nicehash->orderid)
			{
				$res = fetch_url("https://www.nicehash.com/api?method=orders.remove&id=$apiid&key=$apikey&algo=$i&order=$nicehash->orderid");
				debuglog($res);
				
				$nicehash->orderid = null;
			}

			$nicehash->btc = null;
			$nicehash->price = null;
			$nicehash->speed = null;
			$nicehash->last_decrease = null;
				
			$nicehash->save();
			continue;
		}
		
		$price = dboscalar("select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
		$minprice = $price*0.5;
		$setprice = $price*0.7;
		$maxprice = $price*0.9;
		$cancelprice = $price*1.1;
		
		$res = fetch_url("https://www.nicehash.com/api?method=orders.get&my&id=$apiid&key=$apikey&algo=$i");
		if(!$res) break;

		$a = json_decode($res);
		if(count($a->result->orders) == 0)
		{
			if($balance < $amount) continue;
			$port = getAlgoPort($algo);
			
			$res = fetch_url("https://www.nicehash.com/api?method=orders.create&id=$apiid&key=$apikey&algo=$i&amount=$amount&price=$setprice&limit=0&pool_host=yaamp.com&pool_port=$port&pool_user=$deposit&pool_pass=xx");
			debuglog($res);

			$nicehash->last_decrease = time();
			$nicehash->save();
			
			continue;
		}

		$order = $a->result->orders[0];
		debuglog("$algo $order->price $minprice $setprice $maxprice $cancelprice");
			
		$nicehash->orderid = $order->id;
		$nicehash->btc = $order->btc_avail;
		$nicehash->workers = $order->workers;
		$nicehash->price = $order->price;
		$nicehash->speed = $order->limit_speed;
		$nicehash->accepted = $order->accepted_speed;
		$nicehash->rejected = $order->rejected_speed;

		if($order->price > $cancelprice && $order->workers > 0)
		{
			debuglog("* cancel order $algo");
			
			$res = fetch_url("https://www.nicehash.com/api?method=orders.remove&id=$apiid&key=$apikey&algo=$i&order=$order->id");
			debuglog($res);
		}
		
		else if($order->price > $maxprice && $order->limit_speed == 0)
		{
			debuglog("* decrease speed $algo");
			
			$res = fetch_url("https://www.nicehash.com/api?method=orders.set.limit&id=$apiid&key=$apikey&algo=$i&order=$order->id&limit=0.05");
			debuglog($res);
		}
		
		else if($order->price > $maxprice && $nicehash->last_decrease+10*60 < time())
		{
			debuglog("* decrease price $algo");
			
			$res = fetch_url("https://www.nicehash.com/api?method=orders.set.price.decrease&id=$apiid&key=$apikey&algo=$i&order=$order->id");
			debuglog($res);
			
			$nicehash->last_decrease = time();
		}
		
		else if($order->price < $minprice && $order->workers <= 0)
		{
			debuglog("* increase price $algo");

			$res = fetch_url("https://www.nicehash.com/api?method=orders.set.price&id=$apiid&key=$apikey&algo=$i&order=$order->id&price=$setprice");
			debuglog($res);
		}
		
		else if($order->price < $maxprice && $order->limit_speed == 0.05)
		{
			debuglog("* increase speed $algo");
			
			$res = fetch_url("https://www.nicehash.com/api?method=orders.set.limit&id=$apiid&key=$apikey&algo=$i&order=$order->id&limit=0");
			debuglog($res);
		}

		$nicehash->save();
	}
	
}








