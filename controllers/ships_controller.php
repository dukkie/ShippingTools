<?php
class ShipsController extends AppController {

	var $name = 'Ships';
	function index() {
		// 地域をまず取得
		$region_id;
		if (empty($this->data['region'])) {
			$region_id = 1;
		} else {
			$region_id = $this->data['region'];
		}
		$region_arr = array();
		$r_sql="select region_id, region_name from regions";
		$r_rows = $this->Ship->query($r_sql);
		foreach ( $r_rows as $row ) {
			$e_region_id = $row['regions']['region_id'];
			$region_name = $row['regions']['region_name'];
			$region_arr[$e_region_id] =  $region_name;
		}
		//- アイテムの配列を作成
		$lps = array();
		$items = array();
		$sql="select item_name, item_short_name from items where status = 1";
		$rows = $this->Ship->query($sql);
		foreach ( $rows as $row ) {
			$item = $row['items']['item_short_name'];
			$items[$item] = $row['items']['item_name'];
			array_push($lps, $item);
		}
		// 輸送手段の配列作成
		$ships = array();
		$shipnames = array();
		$sql="select s_m_name, s_m_short_name from shipping_methods";
		$rows = $this->Ship->query($sql);
		foreach ( $rows as $row ) {
			$method = $row['shipping_methods']['s_m_short_name'];
			array_push($ships, $method);
			$shipnames[$method] = $row['shipping_methods']['s_m_name'];
		}

    		// - 送料
		$sf = array ();
		$sql="select 
			i.item_name,
			i.item_short_name,
			m.s_m_name,
			m.s_m_short_name,
			s.first_fee,
			s.add_fee,
			s.weight_upper
			from items i, shipping_methods m, ships s
			where i.item_id = s.item_id
			and m.s_m_id = s.s_m_id
			and s.region_id = $region_id
			and i.status = 1
			and s.status = 1
			order by i.item_id, m.s_m_id";
		$rows = $this->Ship->query($sql);
    		$w_limit = 0;
		foreach ( $rows as $row ) {
			$item = $row['i']['item_short_name'];
			$method = $row['m']['s_m_short_name'];
			$sf[$method]['first'][$item] = $row['s']['first_fee'];
			$sf[$method]['add'][$item] = $row['s']['add_fee'];
    			// - 1BOXあたりの重さ上限取得。相乗り梱包のため、最も大きい値に合わせる。
			$w = $row['s']['weight_upper'];
			if ( $w_limit < $w ) {
				$w_limit = $w;
			}
		}
		// 結果をdataにセットして変数を共有
		$this->data['items'] = $items;
		$this->data['lps'] = $lps;
		$this->data['ships'] = $ships;
		$this->data['shipnames'] = $shipnames;
		$this->data['sf'] = $sf;
		$this->data['region_arr'] = $region_arr;

		if (!empty($this->data['Ship'])) {
			foreach ( $lps as $lp ) {
				$ammount[$lp] = $this->data["Ship"][$lp];
			}
			// 例外処理
			// gatefold service
			if ( $ammount['glp'] ) {
				if ( $ammount['glp'] > 1 ) {
					$minus_glp = ceil ($ammount['glp'] / 2 );
					if ( $minus_glp % 2 == 1 ) {
						$minus_glp++;
					}
					$ammount['lp'] += $minus_glp;
					$ammount['glp'] -= $minus_glp;
				} elseif ( $ammount['glp'] == 1 ) {
					$ammount['lp'] = 1;
					$ammount['glp'] = '';
				}

				//1より小さければ？
				if ( $ammount['glp'] < 1 ) {
					$ammount['glp'] = '';
				}
			}

			// アイテムの重量を取得
			$weight = array ();
			$sql='SELECT item_short_name, item_weight FROM items WHERE status = 1';
			$rows = $this->Ship->query($sql);
			foreach ( $rows as $row ) {
				$item = $row['items']['item_short_name'];
				$w = $row['items']['item_weight'];
				$weight[$item] = $w;
			}

			// 結果を格納する配列
			$box = array();	//例：$box[0]['lp'] = 5枚、$box[0]['glp'] = 2枚...
			// 現在仕分けに使っている荷物の番号を記憶する。 = 0から数える // initファンクションにまとめたい
			$current_box_num = 0;
			$this->init_box($box, 'total', $w_limit, $lps, $ships); //BOXを初期化
			$this->init_box($box, $current_box_num, $w_limit, $lps, $ships); //BOXを初期化
			foreach ( $lps as $lp ) {
				$box['total']['firstnum'][$lp] = 0;
			}

			// * 仕分け * //
			// 重い荷物から順番にBOXに詰めていく  -> これは要相談。値段が変わってくるだろう。
			$lps_r = $lps; // 2012-01-07
			// $lps_r = array_reverse( $lps );

			for ( $i = 0; $i < count($lps_r); $i++ ) {
				$lp = $lps_r[$i];
				// 数量が1以上なら処理する
				while ( $ammount[$lp] ) {
					// 現在仕掛中の荷物ではダメな場合
					if ( $box[$current_box_num]['rest'] < $weight[$lp] ) {
						// 残りのアイテムで積み込めるものが無いか、求める。
						for ( $j = $i + 1; $j < ( count($lps_r) - 1 ); $j++ ) {
							$next_item = $lps_r[$j];
							if ( $ammount[$next_item] > 0 ) {
								if ( $box[$current_box_num]['rest'] >= $weight[$next_item] ) {
									// 余りスペースがあるなら、詰め込めるだけ積み込む
									$can_insert =  floor ( $box[$current_box_num]['rest'] / $weight[$next_item] );
									for ( $k = 1; $k < $can_insert; $k++ ) {
										$this->insert_box($box, $current_box_num, $next_item, $weight, $ammount, $sf, $ships); //詰め込み処理
										$box[$current_box_num]['count']++;
									}
								}
							}
						}
						// 次の荷物に進む。
						$current_box_num++;
						$this->init_box($box, $current_box_num, $w_limit, $lps, $ships); //BOXを初期化
					}
					// 詰め込んでいく
					$this->insert_box($box, $current_box_num, $lp, $weight, $ammount, $sf, $ships); //詰め込み処理
					$box[$current_box_num]['count']++;
				}
			}
			// ダンプ
			//print var_dump($box);
			$this->data['box'] = $box;
		}
	}
	// BOX初期化処理
	function init_box( &$box, $current, $w_limit, $lps, $ships ) {
		// 収容可能重量を記憶する
		$box[$current]['rest'] = $w_limit;
		for ( $i=0; $i < count($ships); $i++ ) {
			$s = $ships[$i];
			$box[$current]['sf'][$s] = 0;        //値段はゼロ円スタート
			$box[$current]['detail'][$s] = '';   //総額計算式を記憶
		}
		$box[$current]['count'] = 0;                    //BOX内のカウンター
		$box[$current]['weight'] = 0;                   //BOX重量
		if ( $current == 'total' ) {
			foreach ( $ships as $s ) {
				$box['total']['sf'][$s] = 0;
			}
			$box['total']['weight'] = 0;
		}	
		for ( $i=0; $i < count($lps); $i++ )  {
			$lp = $lps[$i];
			$box[$current][$lp] = 0;
			$box[$current]['weight_by_lps'][$lp] = 0;
			if ( $current == 'total') {
				$box['total']['weight_by_lps'][$lp] = 0;
			}
			$box[$current]['firstnum'][$lp] = 0;
		}
		
	}
	// 詰め込み処理
	function insert_box(&$box, $current, $lp, $weight, &$ammount, $sf, $ships) {
		$box[$current][$lp]++;  //数量を加算
		$box['total'][$lp]++;
		$box['total']['weight'] += $weight[$lp];
		$ammount[$lp]--;                //ノルマを減算
		$box[$current]['rest'] -= $weight[$lp]; //残重量を減算
		$box[$current]['weight'] += $weight[$lp]; //重量を加算       
		$box[$current]['weight_by_lps'][$lp] += $weight[$lp]; //重量を加算       
		$box['total']['weight_by_lps'][$lp] += $weight[$lp]; //重量を加算       
		// 送料計算
		if ( $box[$current]['count'] == '0' )  {
			// 初期送料
			foreach ( $ships as $sm ) {
				$box[$current]['sf'][$sm] += $sf[$sm]['first'][$lp];
				$box[$current]['detail'][$sm] = $sf[$sm]['first'][$lp];
				$box['total']['sf'][$sm] += $sf[$sm]['first'][$lp];
			}
			// BOX の初回荷物数
			$box[$current]['firstnum'][$lp]++;
			// トータルでの初回荷物数
			$box['total']['firstnum'][$lp]++;
		} else {
        		// 追加送料
			foreach ( $ships as $sm ) {
        			$box[$current]['sf'][$sm] += $sf[$sm]['add'][$lp];
        			$box[$current]['detail'][$sm] .= '+' . $sf[$sm]['add'][$lp];
				$box['total']['sf'][$sm] += $sf[$sm]['add'][$lp];
			}
		}
	}
}
?>
