<?php
/*
Plugin Name: Significant Tags
Plugin URI: http://wordpress.org/extend/plugins/significant-tags/
Description: Adds parameters to Wordpress' tagcloud for filtering out insignificant tags.
Version: 1.0
Author: Raphael Reitzig
Author URI: http://lmazy.verrech.net/
License: GPL2
*/
?>
<?php
/*  Copyright 2011 Raphael Reitzig (wordpress@verrech.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php

/**
 * Main class of the Significant Tags plugin. See accompanying readme.txt
 * for details.
 *
 * @author Raphael Reitzig
 * @version 1.0
 */
class SignificantTags {

  /**
   * Creates a new instance.
   */
  function __construct() {
    add_filter('tag_cloud_sort', array(&$this, 'apply'), 10, 2);
  }

  /**
   * Called by for sorting the tag list in wp_generate_tag_cloud
   * (hook 'tag_cloud_sort').
   * Filters out elements from the specified tag list as dictated by
   * fields 'drop_top' or 'drop_bottom' in $args. Both can hold one of the
   * following value schemes:
   * - X  -- removes the X highest/lowest ranking tags (X integer)
   * - X% -- removes the X percent highest/lowest ranking tags (X integer)
   * - Xs -- removes all tags with more/less than average post count plus/minus
   *         X times standard deviation of average count (X floating number)
   * - Xc -- removes tags with more/less than X posts (X integer)
   * @param array $tags Array of tags to be shown in the tagcloud
   * @param array $args Array of arguments passed to 'wp_tag_cloud'
   * @return array Tags that are not purged
   */
  function apply($tags, $args) {
    // Get tag statistics
    $profile = $this->analyse($tags, 'count');

    // By default, drop nothing
    $drop = array('top' => 0, 'bottom' => 0);

    // We do basically the same for top and bottom drops
    foreach ( $drop as $dir => $foo ) {
      if ( empty($args['drop_'.$dir]) ) {
        continue;
      }
      elseif ( preg_match('/^(\d+)$/', $args['drop_'.$dir], $match) ) {
        // Drop a fixed number tags; normalise input
        $nr = (int)$match[1];
        $drop[$dir] = min(max(0, $nr), sizeof($tags));
      }
      elseif ( preg_match('/^(\d+)%$/', $args['drop_'.$dir], $match) ) {
        // Drop a fixed percentage of tags; normalise input
        $nr = (int)$match[1];
        $drop[$dir] = (int)floor(min(max(0, $nr),100)/100 * sizeof($tags));
      }
      elseif ( preg_match('/^(\d+)c$/', $args['drop_'.$dir], $match) ) {
        // Drop all tags with more/less than a fixed number of posts
        $nr = (int)$match[1];
        $drop[$dir] = $this->count($profile['ranked'], $dir, $nr);
      }
      elseif ( preg_match('/^(-?(?:\d*\.)?\d+)s$/', $args['drop_'.$dir], $match) ) {
        // Drop all tags with more/less posts than some multitude of sigma from mean
        $nr = $profile['mean'] + ($dir == 'top' ? 1 : -1) * (float)$match[1] * sqrt($profile['var']);
        $drop[$dir] = $this->count($profile['ranked'], $dir, $nr);
      }
    }

    // Slice away dropped tails from ranked list.
    $keep = array_slice($profile['ranked'], $drop['bottom'], max(0, sizeof($profile['ranked']) - $drop['bottom'] - $drop['top']), TRUE);

    /* Copy all tags that are not dropped to result array. That
     * keeps the original order. */
    $newtags = array();
    foreach ($tags as $k => $v) {
      if ( !empty($keep[$k]) ) {
        $newtags[$k] = $v;
      }
    }

    /* The sorting code below is essentially the one from Wordpress' category-template.php
     * (only more efficient). Sadly, the library function will not sort after a
     * filter worked on the tag list. */
    if ( 'RAND' == $args['order'] ) {
      shuffle($newtags);
    }
    else {
      $invert = '';
      if ( 'DESC' == $args['order'] ) {
        $invert = '-';
      }

      if ( 'name' == $args['orderby'] ) {
        uasort( $newtags, create_function('$a, $b', 'return '.$invert.'strnatcasecmp($a->name, $b->name);') );
      }
      else {
        uasort( $newtags, create_function('$a, $b', 'return '.$invert.'($a->count > $b->count);') );
      }
    }

    return $newtags;
  }

  /**
   * Counts how many elements in $arr are smaller (if $dir holds "bottom")
   * resp larger (if $dir holds "top") than $nr. $arr is assumed
   * to be sorted in ascending order.
   * @param array $arr The array (of integers) to count outliers in
   * @param string $dir Defines the end of the array to cound from.
   * @param int $nr The number to compare elements with
   * @return int The number of elements smaller/larger than $nr
   */
  private function count(&$arr, $dir, $nr) {
    $i = 0;

    /* We move through the array from the left; if we want to count
     * high ranking tags, we have to reverse the ranking temporarily. */
    if ( $dir == 'top' ) { array_reverse($arr, TRUE); }

    /* Move through the ranked list and count how many tags have to
     * be dropped. */
    foreach ( $arr as $c ) {
      if ( ($dir == 'bottom' && $c >= $nr) || ($dir == 'top' && $c <= $nr) ) {
        break;
      }
      else {
        $i += 1;
      }
    }

    if ( $dir == 'top' ) { array_reverse($arr, TRUE); }

    return $i;
  }

  /**
   * Computes mean, variance and ranking of the specified value arrays,
   * using element $key for the computations.
   * @param array $vals An array of arrays to be analysed
   * @param string $key The field of the individual arrays which contain the numeric
   *                    value to be used.
   * @return array An array with mean ('mean'), variance ('var') and an array
   *               of all $key values ranked ascendingly ('ranked').
   */
  private function analyse(&$vals, $key) {
    $return = array('mean' => 0, 'var' => 0, 'ranked' => array());

    if ( !empty($vals) ) {
      $sum = 0;
      foreach ( $vals as $k => $v ) {
        $sum += $v->$key;
        $return['ranked'] [$k]= $v->$key;
      }
      $return['mean'] = $sum / sizeof($vals);

      $sum = 0;
      foreach ( $vals as $val ) {
        $sum += ($val->$key - $return['mean']) * ($val->$key - $return['mean']);
      }
      $return['var'] = $sum / sizeof($vals);

      asort($return['ranked']);
    }

    return $return;
  }
}

new SignificantTags();

?>
