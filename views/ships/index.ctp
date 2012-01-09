<!-- calculate shipping fees to Voice Studio. author dukkiedukkie, marie ao -->
<HTML>
<HEAD>
<TITLE>★SHOPPING LIST★</TITLE>
</HEAD>
<BODY>
<h1>VOICE STUDIO: Shipping Tools</h1>
<style type=text/css>
<!--
body,td {
font-size:9pt;
color:#000000;
font-family:'Osaka';
line-height:11pt;}
-->
</style>

<?php echo $form->create('Ship'); ?>
<table border="0"cellspacing="1"bgcolor="#222222">
<tr>
<td width=50 bgcolor="white"><CENTER>：：：：</td>
<?php
foreach ($this->data['lps'] as $lp ) {
	$item_name = $this->data['items'][$lp];
	print "<td width=150 bgcolor=white colspan=3><center>$item_name</td>\n";
}
?>
</tr>
<tr>
<td width=50 bgcolor="white"><CENTER>：：：：</td>
<?php
foreach ($this->data['lps'] as $lp ) {
	$ammount_form = $form->input($lp);
	print "<td width=150 bgcolor=white colspan=3>$ammount_form</td>\n";
}
?>
<td width=150 bgcolor="white" colspan=3><?php echo $form->end('Calculate Shipping Fee'); ?></td>
</tr>
<tr>
<td width=50 bgcolor="white"><CENTER>　</td>
<?php
foreach ($this->data['lps'] as $lp ) {
	print <<<EOF
<TD bgcolor="white" width="50"><CENTER>FIRST</TD>
<TD bgcolor="white" width="50"><CENTER>ADD</TD>
<TD bgcolor="white" width="50"><CENTER>WEIGHT</TD>
EOF;
}
?>
<TD bgcolor="white" width="50"><CENTER>Weight Total</TD>

<?php
foreach ($this->data['ships'] as $ship ) {
	print "<TD bgcolor=white width=50><CENTER>$ship</TD>\n";
}
?>
</tr>

<!-- トータル部分の出力 -->
<tr> <td width=50 bgcolor="white"><CENTER><B>TOTAL</B></td>
<?php
if ( ! empty($this->data['box']['total']) ) {
	$total = $this->data['box']['total'];
	foreach ( $this->data['lps'] as $lp) {
		$first = '-';
		$add = '-';
		$weight = '-';

		if ( $total[$lp] ) {
			$first = $total['firstnum'][$lp];
			$add = $total[$lp] - $first;
			$weight = $total['weight_by_lps'][$lp];
		}
		print "<TD bgcolor=white width=50><CENTER>$first</TD>\n";
		print "<TD bgcolor=white width=50><CENTER>$add</TD>\n";
		print "<TD bgcolor=white width=50><CENTER>$weight</TD>\n";
	}
	$weight=$total['weight'];
	print "<TD bgcolor=white width=50><CENTER>$weight</TD>\n";
	foreach ( $this->data['ships'] as $ship ) {
		$sf=$total['sf'][$ship];
		print "<TD bgcolor=white width=50><CENTER>$sf</TD>\n";
	}
}
?>
</tr>

<?php 
// BOXごとの表示部分
$max = -1;
if ( ! empty($this->data['box']) ) {
	foreach ( $this->data['box'] as $key => $value ) {
		if ( preg_match("/^[0-9]+$/", $key)) {
			if ( $max <= $key ) {
				$max = $key;
			}
		}
	}
	// 荷物ごとの結果出力
	$box_num = 0;
	for ($i = 0; $i <= $max; $i++ ) { 
	//for ($i = $max; $i >= 0; $i --) { 
		if ( ! empty($this->data['box'][$i]) ) {
			$fee = '-';
			$box_weight = '-';
			$box_num++;  
			$one_box = $this->data['box'][$i];
			print "<tr> <td width=50 bgcolor=white><CENTER>BOX$box_num</td>\n";

			foreach ( $this->data['lps'] as $lp ) {
				//初期化
				$one_box_first = '-';
				$one_box_add = '-';
				$weight = '-';
				if ( $one_box[$lp]) {
					if ( ! empty($one_box['firstnum'][$lp]) ) {
						$one_box_first = $one_box['firstnum'][$lp];
						$one_box_add = $one_box[$lp] - $one_box_first;
					} else {
						$one_box_add = $one_box[$lp];
					}
					$weight = $one_box['weight_by_lps'][$lp];
				}
				print "<TD bgcolor=white width=50><CENTER>$one_box_first</TD>\n";
				print "<TD bgcolor=white width=50><CENTER>$one_box_add</TD>\n";
				print "<TD bgcolor=white width=50><CENTER>$weight</TD>\n";
			}
			$box_weight = $one_box['weight'];
			print "<TD bgcolor=white width=50><CENTER>$box_weight</TD>\n";
			foreach ( $this->data['ships'] as $ship ) {
				$fee = $one_box['sf'][$ship];
				print "<TD bgcolor=white width=50><CENTER>$fee</TD>\n";
			}
			print "</tr>\n";
		}
	}
}
?>


<?php
foreach ($this->data['ships'] as $ship ) {
	print "<tr> <td width=50 bgcolor=white><CENTER><i>$ship</i></td>\n";
	foreach ($this->data['lps'] as $lp ) {
		$first = $this->data['sf'][$ship]['first'][$lp];
		$add = $this->data['sf'][$ship]['add'][$lp];
		print "<TD bgcolor=white width=50><CENTER><i>$first</i></TD>\n";
		print "<TD bgcolor=white width=50><CENTER><i>$add</i></TD>\n";
		print "<TD bgcolor=white width=50><CENTER></TD>\n";
	}
	print "<TD bgcolor=white width=50><CENTER></TD>\n";
	print "</tr>\n";
}
?>
</TABLE>

<!-- 地域選択 -->
<HR />
<B>* Select Region (default US&Europe )*</B>
<BR />
<?php
$radio_arr = array();
foreach ( $this->data['region_arr'] as $key => $value ) {
	$radio_arr[$key] = $value;
}
print $form->radio('region',$radio_arr);
print "<BR />\n";
?>
<HR />

<!-- INVOICE用メール出力 -->
<?php
if ( ! empty($this->data['box']) ) {
	print "<HR />\n";
	$total = $this->data['box']['total'];
	// 後で直す。
	$items = 0;
	foreach ($this->data['lps'] as $lp ) {
		$items += $total[$lp];
	}
	// $items = $total['lp'] + $total['glp'] + $total['dlp'] + $total['ep'];
	foreach ( $this->data['box'] as $key => $value ) {
		if ( preg_match("/^[0-9]+$/", $key)) {
			if ( $max <= $key ) {
				$max = $key;
			}
		}
	}
	$boxes = $max + 1;
        print <<<EOF
<B>* INVOICE template for Customers *</B>
<HR />
<PRE>
Hello,
Thank you for buying it!!
Please select it from a delivery method here.
Please pay by the delivery method of hope.

EOF;

	if ( $boxes > 1 ) {
		print <<<EOF

$items items total weight exceeds the limit of 2kg for small
packet package.Therefore, I can either ship them by $boxes
separate shipments if you want a small packet shipment.

EOF;
	}
	print <<<EOF

***** Shipping information *****

EOF;
	foreach ( $this->data['ships'] as $ship ) {
		$name = $this->data['shipnames'][$ship];
		for ( $i = $max; $i >= 0; $i-- ) {
			if ( ! empty($this->data['box'][$i] )) {
				if ( $i == $max ) {
					$sf_detail = $this->data['box'][$i]['sf'][$ship];
				} else {
					$sf_detail .= '+' . $this->data['box'][$i]['sf'][$ship];
				}
			}
		}
		$sf_total = $this->data['box']['total']['sf'][$ship];
		// 同額の場合、1ドルサービス
		if ( $ship == 'AIR' ) {
			if ( $sf_total == $this->data['box']['total']['sf']['SAL-R'] ) {
				for ( $i = $max; $i >= 0; $i-- ) {
					if ( $i == $max ) {
						$tmp = $this->data['box'][$i]['sf'][$ship] - 1;
						$sf_detail = $tmp;
					} else {
						$tmp = $this->data['box'][$i]['sf'][$ship];
						$tmp--;
						$sf_detail .= '+' . $tmp;
					}
				}
				$tmp = $this->data['box']['total']['sf'][$ship] - $boxes;
				$sf_total = $tmp;
			}
		}

		if ( $boxes == 1 ) {
			print "$name --- \$$sf_detail,\n";
		} else {
			print "$name --- \$$sf_detail(\$$sf_total),\n";
		}
		
	}	

	print <<<EOF

I was going to send the invoice. Please continue your
favors toward the confirmation. We cannot refund the money
if you don't select the registered mail option, so I
highly recommend to choose the shipping method with insurance.
Thank you very much!!

*** Delivery method and arrival days ***

A: Registered Air Mail (7 to14 days) 
** Insurance($60.00)

B: Registered SAL (14 to 28 days) 
** Insurance($60.00)

C: Air Mail (7 to14 days) 
** NO Tracking!!

D: SAL (14 to 28 days) 
** NO Tracking!!


</PRE>
EOF;

}
?>
