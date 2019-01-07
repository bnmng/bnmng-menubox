<?php
/*
Plugin Name: Menubox
Plugin URI: http://menubox.bnmng.com
Description: Displays a menubox in a page
Version: 1.0
Author: bnmng
Author URI: http://bnmng.com
*/

function bnmng_menubox_shortcode( $atts ) {
	$atts = shortcode_atts ( array (
		'menu'                     => '',
		'occurance'                => '1',
		'chapter_parent'           => '0',
		'chapter_parent_occurance' => '1',
		'child_level'              => '0',
		'use_divs'                 => '',
		'title'                    => 'Navigation',
		'class'                    => 'bnmng-menubox',
		'hide'                     => '',
		'show'                     => '',
		'labels'                   => '',
	) , $atts, 'menubox' );

	$post = get_post();

	if ( ( ! $post ) || '' == $atts['menu'] ) {
		return;
	}

	$all_items = wp_get_nav_menu_items( $atts['menu'] );
	if ( ! $all_items ) {
		return;
	}

	if ( is_int( $atts['occurance'] ) ) {
		$target_occurance = intval( $atts['occurance'] );
	} else {
		$target_occurance = 0;
	}
	$loop_occurance = 0;

	$atts_show = array();
	if ( trim( $atts['show'] ) > '' ) {
		$atts_show = array_map( function( $key ) {
			return strtolower( trim( $key ) );
		}, explode( ',', trim( $atts['show'] ) ) );
	}

	$atts_hide = array();
	if ( trim( $atts['hide'] ) > '' ) {
		$atts_hide = array_map( function( $key ) {
			return strtolower( trim( $key ) );
		}, explode( ',', trim( $atts['hide'] ) ) );
	}
	$labels = array(
		'title' =>            'Navigation',
		'previous_chapter' => 'Previous Chapter',
		'this_chapter' =>     'This Chapter',
		'next_chapter' =>     'Next Chapter',
		'previous_item' =>    'Previous Item',
		'next_item' =>        'Next Item',
		'parent' =>           'Parent Item',
		'children' =>         'Child Items',
	);

	$atts_labels = array();
	if ( trim( $atts['labels'] ) > '' ) {
		$atts['labels'] = str_replace( '\\;', 'bnmngsemicolon', $atts['labels'] );
		$atts_labels = array_map( function( $pair ) {
			return trim( str_replace( 'bnmngsemicolon', ';', $pair ) );
		}, explode( ';', trim( $atts['labels'] ) ) );
		foreach( $atts_labels as $label_pair ) {

			$label_pair = str_replace( '\\:', 'bnmngcolon', $label_pair );
			$keyed_pair = array_map( function( $pair ) {
				return trim( str_replace( 'bnmngcolon', ':', $pair ) );
			}, explode( ':', trim( $label_pair ) ) );
			if( 2 == count( $keyed_pair ) ) {
				$labels[ $keyed_pair[0] ] = $keyed_pair[1];
			}
		}
	}

	$show_keys = array(
		'next_item',
		'previous_item',
		'next_chapter',
		'previous_chapter',
		'this_chapter',
		'parent',
		'children',
		'title',
		'labels',
	);

	$show = array();	
	foreach( $show_keys as $key ) {
		$show[ $key ] = false;
		if ( ( ! count( $atts_show ) ) || in_array( $key, $atts_show ) ) {
			if ( ! in_array( $key, $atts_hide ) ) {
				$show[ $key ] = true;
			}
		}
	}

	if ( ! class_exists('bnmng_Walker') ) {
		class bnmng_Walker extends Walker {

			function __construct() {
				$args = func_get_args();
				$this->this_item = $args[0];
				$this->class = $args[1];
				if ( '' < $args[2] && 'false' != strtolower( trim( $args[2] ) ) && '0' != trim( $args[2] ) ) {
					$this->ul = 'div';
					$this->li = 'div';
				} else {
					$this->ul = 'ul';
					$this->li = 'li';
				}
			}
		
			var $db_fields = array(
				'parent' => 'menu_item_parent',
				'id'     => 'db_id'
			);
			function start_lvl( &$output, $depth = 0, $args = array() ) {
				$output .= '<' . $this->ul . ' class="' . $this->class . '">' . "\n";
			}
			function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
				$output .= '<' . $this->li . ' class="' . $this->class . '"><a href="' . $item->url . '">' . $item->title . '</a>';
			}
			function end_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
				$output .= '</' . $this->li . '>';
			}
			function end_lvl( &$output, $depth = 0, $args = array() ) {
				$output .= '</' . $this->ul . '>';
			}

			function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {
			
				if ( $element->ID == $this->this_item->ID ) {
					$output .= '<' . $this->li . ' class="' . $this->class . '">' . $element->title . '';
					return;
				}
				parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
			}
		}
	}

	for ( $i = 0; count( $all_items ) > $i; $i++ ) {
		if( $post->ID == $all_items[ $i ]->object_id ) {
			if( $loop_occurance == $target_occurance ) {
				$this_item = $all_items[ $i ];
				$this_i = $i;
				break;
			} else {
				$loop_occurance++;
			}
		}
	}

	$found = array(
		'this_chapter'     =>false,
		'parent'           =>false,
		'previous_chapter' =>false,
	);
	for ( $i = ( $this_i ); 0 <= $i; $i-- ) {
		if ( ! $found['parent'] ) {
			if ( $this_item->menu_item_parent == $all_items[ $i ]->ID ) {
				$parent = $all_items[ $i ];
				$found['parent'] = true;
			}
		}
		if ( ! $found['this_chapter'] ) {
			if ( $atts['chapter_parent'] == $all_items[ $i ]->menu_item_parent ) {
				$this_chapter = $all_items[ $i ];
				$found['this_chapter'] = true;
			}
		} elseif ( ! $found['previous_chapter'] ) {
			if ( $atts['chapter_parent'] == $all_items[ $i ]->menu_item_parent ) {
				$previous_chapter = $all_items[ $i ];
				$found['previous_chapter'] = true;
			}
		}
	}

	$children = array();
	$descendents_parents = array( $this_item->ID );
	for ( $i = ( $this_i + 1 ); count( $all_items ) > $i; $i++ ) {
		if ( in_array( $all_items[ $i ]->menu_item_parent, $descendents_parents ) ) {
			$descendents_parents[] = $all_items[ $i ]->ID;
			$children[] = $all_items[ $i ];
		} elseif( $atts['chapter_parent'] == $all_items[ $i ]->menu_item_parent ) {
			$next_chapter = $all_items[ $i ];
			break;
		}
	}

	if ( 0 < $this_i ) {
		$previous_item = $all_items[ $this_i - 1 ];
	}
	if ( count( $all_items ) > $this_i ) {
		$next_item = $all_items[ $this_i + 1 ];
	}
	
	$bnmng_walker = new bnmng_Walker( $this_item, $atts['class'],  $atts['use_divs'] );

	$menu = '';

	$menu .= '<div class="' . $atts['class'] . '" >' . "\n";

	if ( $show['title'] ) {
		$menu .= '	<div class="' . $atts['class'] . '-title" >' . "\n";
		$menu .= '		' . $labels['title'] . "\n";
		$menu .= '	</div>' . "\n";
	}

	if ( $show['previous_chapter'] || $show['next_chapter'] || $show['this_chapter'] ) {

		$menu .= '	<div class="' . $atts['class'] . '-chapters" >' . "\n";

		if ( $show['previous_chapter'] ) {

			$menu .= '		<div class="' . $atts['class'] . '-chapter-previous" >' . "\n";
			if ( isset( $previous_chapter ) ) {
				$menu .= '			<div class="' . $atts['class'] . '-label" >' . "\n";
				$menu .= '				' . $labels['previous_chapter'] . "\n";
				$menu .= '			</div>' . "\n";
				$menu .= '			<div class="' . $atts['class'] . '-link" >' . "\n";
				$menu .= '				<a href="' . $previous_chapter->url . '">' . $previous_chapter->title . '</a>' . "\n";
				$menu .= '			</div>' . "\n";
			} else {
				$menu .= '			&nbsp;' . "\n";
			}
			$menu .= '		</div>' . "\n";
		}

		if ( $show['this_chapter'] ) {

			$menu .= '		<div class="' . $atts['class'] . '-chapter-this" >' . "\n";
			if ( isset( $this_chapter ) ) {
				$menu .= '			<div class="' . $atts['class'] . '-label" >' . "\n";
				$menu .= '				' . $labels['this_chapter'] . "\n";
				$menu .= '			</div>' . "\n";
				$menu .= '			<div class="' . $atts['class'] . '-link" >' . "\n";
				$menu .= '				<a href="' . $this_chapter->url . '">' . $this_chapter->title . '</a>' . "\n";
				$menu .= '			</div>' . "\n";
			} else {
				$menu .= '			&nbsp;' . "\n";
			}
			$menu .= '		</div>' . "\n";
		}

		if ( $show['next_chapter'] ) {
			$menu .= '		<div class="' . $atts['class'] . '-chapter-next" >' . "\n";
			if ( isset( $next_chapter ) ) {
				$menu .= '			<div class="' . $atts['class'] . '-label" >' . "\n";
				$menu .= '				' . $labels['next_chapter'] . "\n";
				$menu .= '			</div>' . "\n";
				$menu .= '			<div class="' . $atts['class'] . '-link" >' . "\n";
				$menu .= '				<a href="' . $next_chapter->url . '">' . $next_chapter->title . '</a>' . "\n";
				$menu .= '			</div>' . "\n";
			} else {
				$menu .= '			&nbsp;' . "\n";
			}
			$menu .= '		</div>' . "\n";
		}

		$menu .= '	</div>' . "\n";
	}

	if ( $show['previous_item'] || $show['next_item'] || $show['parent'] ) {

		$menu .= '	<div class="' . $atts['class'] . '-items" >' . "\n";

		if ( $show['previous_item'] ) {

			$menu .= '		<div class="' . $atts['class'] . '-item-previous" >' . "\n";

			if ( isset( $previous_item ) ) {
				$menu .= '			<div class="' . $atts['class'] . '-label" >' . "\n";
				$menu .= '				' . $labels['previous_item'] . "\n";
				$menu .= '			</div>' . "\n";
				$menu .= '			<div class="' . $atts['class'] . '-link" >' . "\n";
				$menu .= '				<a href="' . $previous_item->url . '">' . $previous_item->title . '</a>' . "\n";
				$menu .= '			</div>' . "\n";
			} else {
				$menu .= '			&nbsp;' . "\n";
			}

			$menu .= '		</div>' . "\n";
		}

		if ( $show['parent'] ) {

			$menu .= '		<div class="' . $atts['class'] . '-item-parent" >' . "\n";

			if ( isset( $parent ) ) {
				$menu .= '			<div class="' . $atts['class'] . '-label" >' . "\n";
				$menu .= '				' . $labels['parent'] . "\n";
				$menu .= '			</div>' . "\n";
				$menu .= '			<div class="' . $atts['class'] . '-link" >' . "\n";
				$menu .= '				<a href="' . $parent->url . '">' . $parent->title . '</a>' . "\n";
				$menu .= '			</div>' . "\n";
			} else {
				$menu .= '			&nbsp;' . "\n";
			}

			$menu .= '		</div>' . "\n";
		}

		if ( $show['next_item'] ) {

		$menu .= '		<div class="' . $atts['class'] . '-item-next" >' . "\n";

			if ( isset( $next_item ) ) {
				$menu .= '			<div class="' . $atts['class'] . '-label" >' . "\n";
				$menu .= '				' . $labels['next_item'] . "\n";
				$menu .= '			</div>' . "\n";
				$menu .= '			<div class="' . $atts['class'] . '-link" >' . "\n";
				$menu .= '				<a href="' . $next_item->url . '">' . $next_item->title . '</a>' . "\n";
				$menu .= '			</div>' . "\n";
			} else {
				$menu .= '			&nbsp;' . "\n";
			}

			$menu .= '		</div>' . "\n";
		}

		$menu .= '	</div>' . "\n";

	}

	if ( $show['children'] ) {
		$menu .= '	<div class="' . $atts['class'] . '-children" >' . "\n";
		$menu .= '		<div class="' . $atts['class'] . '-children-list" >' . "\n";
		if ( count( $children ) ) {
			$menu .= '			<div class="' . $atts['class'] . '-label" >' . "\n";
			$menu .= '				' . $labels['children'] . "\n";
			$menu .= '			</div>' . "\n";
			$menu .= '			<div class="' . $atts['class'] . '-link" >' . "\n";
			$menu .= '				<ul>' . "\n" . $bnmng_walker->walk( $children, intval( $atts['child_level'] ) ) . '			</ul>' . "\n";
			$menu .= '			</div>' . "\n";
		}
		$menu .= '		</div>' . "\n";
		$menu .= '	</div>' . "\n";
	}

	$menu .= '</div>' . "\n";

	return $menu;
}

add_shortcode( 'menubox', 'bnmng_menubox_shortcode' );

function bnmng_menubox_style() {
?>
<style type="text/css">

	div.bnmng-menubox {
		width:100%;
		font-size:.8em;
	}

	div.bnmng-menubox-title {
		width:100%;
		border-top: 1px solid black;
		border-bottom: 1px solid black;
		text-align:center;
	}

	div.bnmng-menubox-chapters {
		display:flex;
		border-bottom:1px solid black;
		margin-bottom:1ex;
	}

	div.bnmng-menubox-chapters > div:first-child {
		flex:0 0 33%;
		text-align:left;
	}

	div.bnmng-menubox-chapters > div:nth-child(2) {
		text-align:center;
		flex:0 0 34%;
	}

	div.bnmng-menubox-chapters > div:last-child {
		flex:0 0 33%;
		text-align:right;
	}

	div.bnmng-menubox-items {
		display:flex;
		border-bottom:1px solid black;
	}

	div.bnmng-menubox-items > div:first-child {
		flex:0 0 33%;
		text-align:left;
	}
	div.bnmng-menubox-items > div:nth-child(2) {
		text-align:center;
		flex:0 0 34%;
	}
	div.bnmng-menubox-items > div:last-child {
		flex:0 0 33%;
		text-align:right;
	}

	div.bnmng-menubox-children {
		display:flex;
		justify-content: center;
		border-bottom: 1px solid black;
	}

	div.bnmng-menubox-children-list {
		flex:0 1 50%;
	}

	div.bnmng-menubox-children-list div.bnmng-menubox-label {
		text-align:center;
	}

	div.bnmng-menubox-label {
		margin-bottom: 0px;
	}
	div.bnmng-menubox-link {
		margin-top: 0px;
	}

</style>
<?php
}

add_action( 'wp_head', 'bnmng_menubox_style' );

