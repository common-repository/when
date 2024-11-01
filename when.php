<?php 
/* 
Plugin Name: When
Plugin URI: http://d723.com/when
Description: <strong>Beta</strong>. <strong><em>When?</em></strong> provides tools for displaying dates and times in the relative formats in which humans think about them (i.e. 3 months ago, early in the morning). Based on date_since() and time_of_day() written by Dunstan Orchard and made into a Wordpress plugin by Michael Heilemann. Works with Wordpress v1.5b or higher.
Author: Derek Gulbranson
Author URI: http://d723.com/
Version: 0.1.1

						 usage for entries/articles: 	<?php when::the_time(); ?>
		usage for comments relative to current time: 	<?php when::the_time("comment"); ?>
   usage for comments relative to article post time: 	<?php when::the_time("both"); ?>
usage to pass entries/articles relative time to php:	<?php when::the_time("",false); ?>


This plugin is based on code from Dunstan Orchard's Blog. Pluginiffied by Michael Heilemann:
http://www.1976design.com/blog/archive/2004/07/23/redesign-time-presentation/

adapted from original code by Natalie Downe
http://blog.natbat.co.uk/archive/2003/Jun/14/time_since

inputs must be unix timestamp (seconds)
$newer_date variable is optional

Notes by Michael Heilemann:
I am by _no_ means a PHP guru. In fact, I couldn't code my way out of a piece of wet cardboard.
But I really wanted to use Dunstan's code on Binary Bonsai, and this is the result. So please,
do not mock me for what is probably some very weird code.

Do however, feel free to mail me (http://binarybonsai.com/about#contact> with suggestions for improving it.
I am using this with WordPress 1.2. Should work fine with WordPress 1.3, but it is still in Alpha, so who knows?
	
*Instructions for use with WordPress 1.2:*
	
Entries:
<?php $entry_datetime = abs(strtotime($post->post_date)); echo time_since($entry_datetime) ?> ago

Comments:
<?php $comment_datetime = abs(strtotime($comment->comment_date)); echo time_since($comment_datetime); ?> ago

Between Entry and Comment:
<?php $entry_datetime = abs(strtotime($post->post_date)); $comment_datetime = abs(strtotime($comment->comment_date)); echo time_since($entry_datetime, $comment_datetime) ?> after the fact.

Please direct support questions to: http://www.flickr.com/groups/binarybonsai/
And gratitude to: http://www.1976design.com/blog/
And sour comments to: null
*/
if( function_exists( 'u2d_register_plugin' ) ){ // for up2date plugin compatibility
        u2d_register_plugin( "when", "http://d723.com/when/version", "1.1.1", 1 );
    }
	
class when {
	function time_since($older_date, $newer_date = false, $chunk_qty=2){
		// array of time period chunks
		$chunks = array(
		array(60 * 60 * 24 * 365 , 'year'),
		array(60 * 60 * 24 * 30 , 'month'),
		array(60 * 60 * 24 * 7, 'week'),
		array(60 * 60 * 24 , 'day'),
		array(60 * 60 , 'hour'),
		array(60 , 'minute'),
		);
		
		// $newer_date will equal false if we want to know the time elapsed between a date and the current time
		// $newer_date will have a value if we want to work out time elapsed between two known dates
		$newer_date = ($newer_date == false) ? current_time('timestamp',1) : $newer_date;
		// difference in seconds
		$since = ($newer_date - $older_date);
		/* debugging stuff
		echo "<br>time(): ".time()." / ".strftime('%Y-%m-%d %H:%M',time());
		echo "<br>current_time('timestamp'): ".current_time('timestamp')." / ".strftime('%Y-%m-%d %H:%M',current_time('timestamp'));
		echo "<br>gmt offset + time: ".(time()+(60*60*get_settings("gmt_offset")))." / ".strftime('%Y-%m-%d %H:%M',time()+(60*60*get_settings("gmt_offset")));
		echo "<br> current time/newer date: $newer_date /".strftime('%Y-%m-%d %H:%M',$newer_date);
		echo "<br> article time/older date: $older_date / ".strftime('%Y-%m-%d %H:%M',$older_date);
		echo '<br>since( $newer_date - $older_date): '.($since).' seconds ';
		echo "<br>gmt offset: ".get_settings("gmt_offset");
		echo "<br>gmt offset mulitplied: ".(60*60*get_settings("gmt_offset"));
		echo "<br> ".(time()+(60*60*get_settings("gmt_offset")));
		echo "<br> <br> RESULT:";
		 */
		// we only want to output two chunks of time here, eg:
		// x years, xx months
		// x days, xx hours
		// so there's only two bits of calculation below:
	
		// step one: the first chunk
		for ($i = 0, $j = count($chunks); $i < $j; $i++){
			$seconds = $chunks[$i][0];
			$name = $chunks[$i][1];
	
			// finding the biggest chunk (if the chunk fits, break)
			if (($count = floor($since / $seconds)) != 0)
				{
				break;
				}
			}
	
		// set output var
		$output = ($count == 1) ? '1 '.$name : "$count {$name}s";
	
		// step two: the second chunk
		if ($i + 1 < $j && $chunk_qty > 1){
			$seconds2 = $chunks[$i + 1][0];
			$name2 = $chunks[$i + 1][1];
			
			if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0)
				{
				// add to output var
				$output .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
				}
			}
		return $output;
		}
	
	function the_time($usage = 'tod', $return='text', $chunks_qty=1){
		/*//when::the_time('date','link',1)
		
			$usage = 	'date' (3 months, 2 days) 
						'tod' time of day (mid-afternoon, lunchtime) 
												
			$return = 	'link' default, echos complete anchor tag <a> to listings on that day
						'text' echos text 
						
			$chunks_qty='1' (3 months) only needed for $usage='date'
						'2' (3 months, 2 days)
		*/
		$o = $usage=='date' ? when::time_since(get_post_time(),false,$chunks_qty) : when::time_of_day(get_post_time()) ;
		$the_uri = when::get_the_day_link();
		if($return == 'link'){
			echo "<a href='$the_uri' title='".get_the_time('Y-m-d',false)." ".get_the_time()."'>$o ";
			_e('ago');
			echo "</a>";
			}
		else{
			echo $o;
			}
		}
	function comment_time($e=true){ // comment date relative to current time
		$o = when::time_since(when::get_comment_ts());
		if($e == true){echo $o;}
		else{return $o;}
		}
	
	function comment_vs_article_time($e=true){ // comment relative to entry
		$o = when::time_since(get_post_time(), when::get_comment_ts());
		if($e == true){echo $o;}
		else{return $o;}
		}
	
	function get_the_day_link(){ // same as WP default template tag get_day_link() but for the current entry
		return get_day_link(strftime('%Y',get_post_time()+0),strftime('%m',get_post_time()+0),strftime('%j',get_post_time()+0));
		}
	function get_post_ts(){
		/* 
		returns the unix timestamp of the current post
		
		NOTE: uses post_date and not post_date_gmt. Although i'm not totally clear on what 
		post_date is (user vs. server time), if time() returns GMT time, you should probably replace $post->post_date with 
		$post->post_date_gmt and in the function get_comment_ts, $comment->comment_date with $comment->comment_date_gmt (i think :). -d723
		
		looks like they figures out people would need the timestamp, so v1.5 has get_post_time()
		 */
		global $post;
		return abs(strtotime($post->post_date));
		}
	function get_comment_ts(){
		global $comment;
		return abs(strtotime($comment->comment_date));
		}
	
function get_time_since_link($type="") // only works for entries right now
	{
		global $post;
		$the_uri = when::get_the_day_link();
		// maybe there's something else that needs to be added to this anchor tag that i don't know about re: accessibility, rel=?
		echo "<a href='$the_uri' title='".the_time('Y-m-d H:i:s',false)."'>".when::time_since(abs(strtotime(the_time('Y-m-d H:i:s',false),0)),false,1)." ago</a>";
	}
	
	// inputs must be unix timestamp (seconds)
	// based on Dunstan's Time of Day code (http://1976design.com/blog/archive/2004/07/23/redesign-time-presentation/)
	function time_of_day($pdate){
		$hour=date('H',$pdate);
		switch($hour)
			{
			case 0:
			case 1:
			case 2:
				$tod = 'the wee hours';
				break;
			case 3:
			case 4:
			case 5:
			case 6:
				$tod = 'terribly early in the morning';
				break;
			case 7:
			case 8:
			case 9:
				$tod = 'early morning';
				break;
			case 10:
				$tod = 'mid-morning';
				break;
			case 11:
				$tod = 'late morning';
				break;
			case 12:
			case 13:
				$tod = 'lunch time';
				break;
			case 14:
				$tod = 'early afternoon';
				break;
			case 15:
			case 16:
				$tod = 'mid-afternoon';
				break;
			case 17:
				$tod = 'late afternoon';
				break;
			case 18:
			case 19:
				$tod = 'early evening';
				break;
			case 20:
			case 21:
				$tod = 'evening time';
				break;
			case 22:
				$tod = 'late evening';
				break;
			case 23:
				$tod = 'late at night';
				break;
			default:
				$tod = '';
				break;
			}
		//return $tod;
		echo $tod;
		}
}
?>