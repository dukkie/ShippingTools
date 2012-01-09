<?php
class ShipsController extends AppController {

	var $name = 'Ships';
	function index() {
		// �ϰ��ޤ�����
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
		//- �����ƥ����������
		$lps = array();
		$items = array();
		$sql="select item_name, item_short_name from items where status = 1";
		$rows = $this->Ship->query($sql);
		foreach ( $rows as $row ) {
			$item = $row['items']['item_short_name'];
			$items[$item] = $row['items']['item_name'];
			array_push($lps, $item);
		}
		// ͢�����ʤ��������
		$ships = array();
		$shipnames = array();
		$sql="select s_m_name, s_m_short_name from shipping_methods";
		$rows = $this->Ship->query($sql);
		foreach ( $rows as $row ) {
			$method = $row['shipping_methods']['s_m_short_name'];
			array_push($ships, $method);
			$shipnames[$method] = $row['shipping_methods']['s_m_name'];
		}

    		// - ����
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
    			// - 1BOX������νŤ���¼��������꺭��Τ��ᡢ�Ǥ��礭���ͤ˹�碌�롣
			$w = $row['s']['weight_upper'];
			if ( $w_limit < $w ) {
				$w_limit = $w;
			}
		}
		// ��̤�data�˥��åȤ����ѿ���ͭ
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
			// �㳰����
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

				//1��꾮������С�
				if ( $ammount['glp'] < 1 ) {
					$ammount['glp'] = '';
				}
			}

			// �����ƥ�ν��̤����
			$weight = array ();
			$sql='SELECT item_short_name, item_weight FROM items WHERE status = 1';
			$rows = $this->Ship->query($sql);
			foreach ( $rows as $row ) {
				$item = $row['items']['item_short_name'];
				$w = $row['items']['item_weight'];
				$weight[$item] = $w;
			}

			// ��̤��Ǽ��������
			$box = array();	//�㡧$box[0]['lp'] = 5�硢$box[0]['glp'] = 2��...
			// ���߻�ʬ���˻ȤäƤ����ʪ���ֹ�򵭲����롣 = 0��������� // init�ե��󥯥����ˤޤȤ᤿��
			$current_box_num = 0;
			$this->init_box($box, 'total', $w_limit, $lps, $ships); //BOX������
			$this->init_box($box, $current_box_num, $w_limit, $lps, $ships); //BOX������
			foreach ( $lps as $lp ) {
				$box['total']['firstnum'][$lp] = 0;
			}

			// * ��ʬ�� * //
			// �Ť���ʪ������֤�BOX�˵ͤ�Ƥ���  -> ����������̡����ʤ��Ѥ�äƤ��������
			$lps_r = $lps; // 2012-01-07
			// $lps_r = array_reverse( $lps );

			for ( $i = 0; $i < count($lps_r); $i++ ) {
				$lp = $lps_r[$i];
				// ���̤�1�ʾ�ʤ��������
				while ( $ammount[$lp] ) {
					// ���߻ų���β�ʪ�Ǥϥ���ʾ��
					if ( $box[$current_box_num]['rest'] < $weight[$lp] ) {
						// �Ĥ�Υ����ƥ���Ѥ߹�����Τ�̵���������롣
						for ( $j = $i + 1; $j < ( count($lps_r) - 1 ); $j++ ) {
							$next_item = $lps_r[$j];
							if ( $ammount[$next_item] > 0 ) {
								if ( $box[$current_box_num]['rest'] >= $weight[$next_item] ) {
									// ;�ꥹ�ڡ���������ʤ顢�ͤ���������Ѥ߹���
									$can_insert =  floor ( $box[$current_box_num]['rest'] / $weight[$next_item] );
									for ( $k = 1; $k < $can_insert; $k++ ) {
										$this->insert_box($box, $current_box_num, $next_item, $weight, $ammount, $sf, $ships); //�ͤ���߽���
										$box[$current_box_num]['count']++;
									}
								}
							}
						}
						// ���β�ʪ�˿ʤࡣ
						$current_box_num++;
						$this->init_box($box, $current_box_num, $w_limit, $lps, $ships); //BOX������
					}
					// �ͤ����Ǥ���
					$this->insert_box($box, $current_box_num, $lp, $weight, $ammount, $sf, $ships); //�ͤ���߽���
					$box[$current_box_num]['count']++;
				}
			}
			// �����
			//print var_dump($box);
			$this->data['box'] = $box;
		}
	}
	// BOX���������
	function init_box( &$box, $current, $w_limit, $lps, $ships ) {
		// ���Ʋ�ǽ���̤򵭲�����
		$box[$current]['rest'] = $w_limit;
		for ( $i=0; $i < count($ships); $i++ ) {
			$s = $ships[$i];
			$box[$current]['sf'][$s] = 0;        //���ʤϥ���ߥ�������
			$box[$current]['detail'][$s] = '';   //��۷׻����򵭲�
		}
		$box[$current]['count'] = 0;                    //BOX��Υ����󥿡�
		$box[$current]['weight'] = 0;                   //BOX����
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
	// �ͤ���߽���
	function insert_box(&$box, $current, $lp, $weight, &$ammount, $sf, $ships) {
		$box[$current][$lp]++;  //���̤�û�
		$box['total'][$lp]++;
		$box['total']['weight'] += $weight[$lp];
		$ammount[$lp]--;                //�Υ�ޤ򸺻�
		$box[$current]['rest'] -= $weight[$lp]; //�Ľ��̤򸺻�
		$box[$current]['weight'] += $weight[$lp]; //���̤�û�       
		$box[$current]['weight_by_lps'][$lp] += $weight[$lp]; //���̤�û�       
		$box['total']['weight_by_lps'][$lp] += $weight[$lp]; //���̤�û�       
		// �����׻�
		if ( $box[$current]['count'] == '0' )  {
			// �������
			foreach ( $ships as $sm ) {
				$box[$current]['sf'][$sm] += $sf[$sm]['first'][$lp];
				$box[$current]['detail'][$sm] = $sf[$sm]['first'][$lp];
				$box['total']['sf'][$sm] += $sf[$sm]['first'][$lp];
			}
			// BOX �ν���ʪ��
			$box[$current]['firstnum'][$lp]++;
			// �ȡ�����Ǥν���ʪ��
			$box['total']['firstnum'][$lp]++;
		} else {
        		// �ɲ�����
			foreach ( $ships as $sm ) {
        			$box[$current]['sf'][$sm] += $sf[$sm]['add'][$lp];
        			$box[$current]['detail'][$sm] .= '+' . $sf[$sm]['add'][$lp];
				$box['total']['sf'][$sm] += $sf[$sm]['add'][$lp];
			}
		}
	}
}
?>
