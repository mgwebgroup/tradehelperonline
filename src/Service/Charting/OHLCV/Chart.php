<?php
/**
* 
* @class Chart
* 
* @brief This class creates charts in .png format and saves them on disk. 
* 
* When adding functions to this class, all functions use array $args as input value. Use verbosely keyed elemens for public functions (possibility to be accessed from outside exists) and simple numerically indexed elements for internal private functions.
* 
* @version 130204
* @author Alex Kay (MGWebGroup)
* 
* Contents:
* __construct() - Overloaded construct method for the class. Checks values for $canvas property, reads axes data, draws chart background, axes, created color table
* add_line_series() - Draws data series on chart as a polyline.
* add_candlestick_series() - Draws candlestick price data. Takes in OHLC information as 4 separate arrays.
* add_xbar_series() - Draws vertical bars on x_axis for the given 'values'.
* add_ybar_series() - Draws horizontal bars on y_axis for the given 'values'.
* add_symbol_series() - Adds annotation to the chart in the form of geometric shapes, text and values.
* draw_rectangle() - Draws filled rectangle with coordinates specified in units (not image pixels) for the given indeces of x_- and y_axes.
* place_text() - Places text at given axis coordinates.
* save_chart() - Saves chart on disk.
* units_to_pxls() - Translates values given in x- and y-coordinates into pixel coordinates on chart.
* get_line_style() - Uses a line prototype, scale and color to build a line pattern.
* apply_transparency() - Applies transparency to a given color name.
* 
*/

namespace App\Service\Charting\OHLCV;

use App\Exception\ChartException;

class Chart
{
	public $canvas = array( 'path' => 'src/Studies/MyStudy/chart.png', 'percent_chart_area' => 90, 'symbol' => '___',
		'ttf_font' => 'assets/fonts/arial.ttf', 'width' => 4800, 'height' => 1800,
		'img_background' => 'gray', 'chart_background' => 'white', ); 
	/** 'chart_area' => Percent of image area used for drawing the chart */
	/** 'symbol' => Symbol name to be displayed on the background of the chart, must be alphanumeric string no longer than 10 chars */
	/** 'ttf_font' => Path to a .ttf font that will be used for all of the chart annotations. Specify path that is relative to the parent .php file this class was called from. */
	/** 'width', 'height' => Width and Height of the entire image for the chart. */
	/** 'img_background', 'chart_background' (optional) Names of colors defined as keys in $this->pen_colors.

	/// You can have up to 10 axes within the chart object. Default is 2 axes for each each direction: x and y. Attempt to add more than 10 axes will trigger class error. Axes can be added during instantiation of the class.
	/** 'upp' => Units per Pixel. Computed later during class construction for each axis. Can be zero when adding additional axes */
	/** 'y_intersect' Where on x_axis y_axis intersects? */
	/** 'major_tick_size', 'minor_tick_size' Tick sizes in pixels. Use sign to flip direction. */
	/** 'categories' Array with categories must be keyed from this the axis' 'min' to 'max' value, and does not need to be pre-sorted. If this array is not NULL values for categories will be displayed instead of values. */
	/** 'minor_interval_count' is a divisor. In other words, by how many parts must the major interval be dividied? */
	/** 'axis_color' Name of the color must be standard name defined as keys in the array $this->pen_colors */
	/** 'show_major_grid', 'show_minor_grid' (optional), flags for displaying major and minor grids. */
	/** 'major_grid_style', Name of line style. Styles are optional and are defined as keys in $this->line_prototypes (see below in the code). $this->line_prototypes['default'] is used if no style is specified either here or during chart instantiation. */
	/** 'major_grid_color', 'minor_grid_color' (optional), 'black' is default */
	/** 'major_grid_scale', 'minor_grid_scale' (optional), uses $this->default['line_scale'] = 1 (defined below) if not specified here */
	/** 'show_major_values', 'show_minor_values' (optional), FALSE is default (do not show values) */
	/** 'print_offset_major', 'print_offset_minor' (optional), offset from the tick mark. Defined in pixels. Uses value defined in 'maj/minor_tick_size' of this same array if none specified here. Also printing of the fond starts from lower-left-hand corner. */
	/** 'font_size' (optional), font sizes to be used on values of the axis. Uses $this->defalut['font_size_x'] (defined below) if not specified here */
	/** 'precision' (optional), uses $this->defalut['precision_x'] (defined below) if not specified here */
	/** 'font_angle' (optional), assigned to zero if not specified here */
	public $x_axis = array(
		0 => array( 'show' => TRUE, 
			'min' => 0, 'max' => 250, 'upp' => 0, 
			'y_intersect' => 250, 
			'major_tick_size' => 8, 'minor_tick_size' => 4, 
			'categories' => array(), 
			'major_interval' => 20, 'minor_interval_count' => 20, 
			'axis_color' => 'black', 
			'show_major_grid' => TRUE, 'show_minor_grid' => FALSE, 
			'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
			'major_grid_color' => 'gray', 'minor_grid_color' => 'gray', 
			'major_grid_scale' => 2, 'minor_grid_scale' => 1, 
			'show_major_values' => TRUE, 'show_minor_values' => FALSE, 
			'print_offset_major' => 35, 'print_offset_minor' => 35, 
			'font_size' => 10,  
			'precision' => 0,  
			'font_angle' => 90,  
		), 
		1 => array( 'show' => FALSE, 
			'min' => 0, 'max' => 100, 'upp' => 0, 'y_intersect' => 0, 
			'major_tick_size' => 8, 'minor_tick_size' => 4, 
			'major_interval' => 10, 'minor_interval_count' => 5, 
			'axis_color' => 'black', 
			'show_major_grid' => FALSE, 'show_minor_grid' => FALSE,
			'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
			'major_grid_color' => 'gray', 'minor_grid_color' => 'gray', 
			'major_grid_scale' => 2, 'minor_grid_scale' => 1, 
			'show_major_values' => FALSE, 'show_minor_values' => FALSE, 
			'print_offset_major' => 10, 'print_offset_minor' => 10, 
			'font_size' => 10,  
			'precision' => 0,  
			'font_angle' => 0, 
			'categories' => NULL,
		),
	);
	public $y_axis = array( 
		0 => array( 'show' => TRUE, 
			'min' => 0, 'max' => 250, 'upp' => 0, 'x_intersect' => 0, 
			'major_tick_size' => 8, 'minor_tick_size' => 4, 
			'major_interval' => 5, 'minor_interval_count' => 2, 
			'axis_color' => 'black', 
			'show_major_grid' => TRUE, 'show_minor_grid' => TRUE,
			'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
			'major_grid_color' => 'gray', 'minor_grid_color' => 'gray', 
			'major_grid_scale' => 2, 'minor_grid_scale' => 1, 
			'show_major_values' => TRUE, 'show_minor_values' => TRUE, 
			'print_offset_major' => 8, 'print_offset_minor' => 8, 
			'font_size' => 10,  
			'precision' => 0,  
			'font_angle' => 0, 
		),
		1 => array( 'show' => FALSE, 
			'min' => 0, 'max' => 100, 'upp' => 0, 'x_intersect' => 100, 
			'major_tick_size' => 8, 'minor_tick_size' => 4, 
			'major_interval' => 10, 'minor_interval_count' => 4, 
			'axis_color' => 'black', 
			'show_major_grid' => FALSE, 'show_minor_grid' => FALSE,
			'major_grid_style' => 'dash', 'minor_grid_style' => 'dash', 
			'major_grid_color' => 'black', 'minor_grid_color' => 'gray', 
			'major_grid_scale' => 2, 'minor_grid_scale' => 1, 
			'show_major_values' => FALSE, 'show_minor_values' => FALSE, 
			'print_offset_major' => 8, 'print_offset_minor' => 8, 
			'font_size' => 7,  
			'precision' => 0,  
			'font_angle' => 0, 
		),
	);
	
	public $n_axes = 10; /**< max number of x and y axes. Example: $n_axes = 10 means there could be up to 10 x-axes, and 10 y-axes */
	
	public $chart = array(); /**< stores pixel coordinates for chart center, four corners and four middle points. */
	
	/**< line prototype are specified as: array( array( <dot or gap> => <number of pixels> ), array( <dot or gap> =>
     * <number of pixels> ),  );
     */
	public $line_prototypes = array( 
		'default' => array( 0 => array( 1 => 8 ), ),
		'dash' => array( 0 => array( 1 => 4, ), 1 => array( 0 => 4, ), ),
		'dash_dot' => array( 0 => array( 1 => 3, ), 1 => array( 0 => 2, ), 2 => array( 1 => 1, ), 3 => array( 0 => 2, ) ),
	);
	public $default = array( 'line_scale' => 1, 'font_size_x' => 10, 'font_size_y' => 10, 'precision_x' => 2, 'precision_y' => 2,	);
	
	public $depth_table = array( 'min' => -100, 'max' => 100, 'd' => 100, 'band' => NULL, );
	/**< d = Divisor to determine number of bands to allocate colors on for a given range of min and max values of symbols. For a range of symbols from -100 to +100, number of bands will be 200 */

	public $pen_colors = array(); /**< array of pen colors consists of standard colors under keys such as 'red', 'black', etc.. and numerical keys, which are created during color allocation for depth values */

	public $symbol_shapes = array( 'circle', 'square', 'triangle-up', 'triangle-down', 'diamond', );
	
	public $image; /**< Chart image object */

	public $errors = array( 
		'100' => 'Array of categories for the x-axis not passed to the set_x_axis function.',
		'102' => 'Could not find file for the specified ttf font. Check default setting of public $canvas[\'ttf_font\'] of the Chart class.',
		'104' => 'Could not find directory specified for saving the chart file.',
		'106' => 'Number of specified axes is above allowable.',
		'108' => 'save_chart function accepts its argument as an array.',
		'110' => 'Array key(s) \'min\' or \'max\' not specified in axis array passed during chart construction.',
		'112' => 'Specify \'y_intersect\' parameter in the x_axis array passed during chart construction',
		'114' => '\'y_intersect\' parameter in the x_axis array must be between min and max values for the axis.',
		'116' => '\'major_interval\' parameter cannot exceed total distance between min and max values for the axis.',
		'118' => '\'minor_interval_count\' parameter cannot be 0.',
		'120' => 'Could not find specified \'axis_color\' in the table of standard colors ($pen_colors).',
		'122' => 'Could not find specified \'major_grid_color\' in the table of standard colors ($pen_colors).',
		'124' => 'Could not find specified \'minor_grid_color\' in the table of standard colors ($pen_colors).',
		'126' => 'Could not find specified \'major_grid_style\' in the table of line prototypes ($line_prototypes).',
		'128' => 'Could not find specified \'minor_grid_style\' in the table of line prototypes ($line_prototypes).',
		'130' => 'Specify \'x_intersect\' parameter in the y_axis array passed during chart construction',
		'132' => '\'x_intersect\' parameter in the y_axis array must be between min and max values for the axis.',
		'134' => '\'categories\' parameter must be an array.',
		'136' => 'Array with the series not passed to the add_line_series() function, or passed value for the series is not an array.',
		'138' => 'Array with the series must have at least two values to build a line with the add_line_series() function.',
		'140' => 'Specified index for the x_axis was not found in the x_axis table.',
		'142' => '\'line_scale\' parameter for add_line_series() function must be more or equal to 1.',
		'144' => '\'open\' parameter for add_candlestick_series() function is empty or not an array or not specified.',
		'146' => '\'high\' parameter for add_candlestick_series() function is empty or not an array or not specified.',
		'148' => '\'low\' parameter for add_candlestick_series() function is empty or not an array or not specified.',
		'150' => '\'close\' parameter for add_candlestick_series() function is empty or not an array or not specified.',
		'152' => 'Specified index for the y_axis was not found in the y_axis table.',
		'154' => 'Array of values not specified.',
		'156' => 'minimum value in array of symbol values is less than the minimum index value in $this->depth table. Colors of symbols cannot be assigned.',
		'158' => 'maximum value in array of symbol values is more than the maximum index value in $this->depth table. Colors of symbols cannot be assigned.',
		'160' => 'One of the four coordinates for the draw_rectangle function is missing.',
		'162' => 'Specify color name from $this->pen_colors for the draw_rectangle function.',
		'164' => 'One of the two coordinates for the place_text function is missing.',
		'166' => 'Text not passed to the place_text function.',
		
	);
	
	public $result; //*< Debug variable used to display class output for testing purposes */

    /**
     *
     * @public __constuct( @args = array( 0 => $canvas, 1 => $x_axis, 2 => $y_axis, [3 => $x_axis1,] [4 => y_axis1,] ) ).
     * Initializes various chart parameters such as chart window coordinates, origin of the chart coordinates,
     *   creates color table for standard colors, creates color table for a scale from -100 to 100,
     *   instantiates image object for the chart,
     *   fills in axes data such as 'upp' (units_per_pixel) values, adds axes if specifed at instantiation.
     *
     * @param array $args [0] Array containing canvas parameters
     * @param array $args [1], $args[2] Arrays with x_axis and y_axis parameters
     *
     * @result obj $image Image object for the Image functions
     *
     * @usage examples: $args( NULL, $x_axis, ) - leave $canvas property to its defaults, overwrite array $x_axis.
     *
     * @throws ChartException
     */
	function __construct( $args = array() ) { 
		
        if ( isset( $args[0]['path'] ) ) {
            $path_parts = pathinfo( $args[0]['path'] );
            if ( is_dir( $path_parts['dirname'] ) ) {
                $this->canvas['path'] = $args[0]['path'];
            }
        }
        if ( isset( $args[0]['width'] ) && is_numeric( $args[0]['width'] ) && $args[0]['width'] > 0 ) $this->canvas['width'] = (int) $args[0]['width'];
        if ( isset( $args[0]['height'] ) && is_numeric( $args[0]['height'] ) && $args[0]['height'] > 0 ) $this->canvas['height'] = (int) $args[0]['height'];
        if ( isset( $args[0]['symbol'] ) && is_string( $args[0]['symbol'] ) && strlen( $args[0]['symbol'] ) <= 10 && ctype_alnum( $args[0]['symbol'] ) ) $this->canvas['symbol'] = $args[0]['symbol'];
        $this->canvas['ttf_font'] = isset($args[0]['ttf_font']) ? realpath($args[0]['ttf_font']) : realpath( $this->canvas['ttf_font'] );
        if ( !is_file( $this->canvas['ttf_font'] ) ) throw new ChartException( $this->errors['102'] );
        if ( isset( $args[0]['ttf_font'] ) && is_file( $args[0]['ttf_font'] ) ) $this->canvas['ttf_font'] = $args[0]['ttf_font'];
        if ( isset( $args[0]['percent_chart_area'] ) && $args[0]['percent_chart_area'] <= 100 && $args[0]['percent_chart_area'] > 0 ) $this->canvas['percent_chart_area'] = $args[0]['percent_chart_area'];
        if ( count( $args ) > $this->n_axes * 2 + 1 ) throw new ChartException( $this->errors['106'].$this->n_axes );

		$canvas_area = $this->canvas['width'] * $this->canvas['height'];
		$this->chart['center'] = array('x' => $this->canvas['width'] / 2, 'y' => $this->canvas['height'] / 2 );

		$chart_area = $canvas_area * $this->canvas['percent_chart_area'] / 100;
		$this->chart['height'] = sqrt( $chart_area / ( $this->canvas['width'] / $this->canvas['height'] ) );
		$this->chart['length'] = $chart_area / $this->chart['height'];
		$this->chart['lower_left'] = array( 'x' => $this->chart['center']['x'] - $this->chart['length'] / 2, 'y' => $this->chart['center']['y'] + $this->chart['height'] / 2, );
		$this->chart['middle_left'] = array( 'x' => $this->chart['lower_left']['x'], 'y' => $this->chart['center']['y'], );
		$this->chart['upper_left'] = array( 'x' => $this->chart['lower_left']['x'], 'y' => $this->chart['lower_left']['y'] - $this->chart['height'], );
		$this->chart['upper_right'] = array( 'x' => $this->chart['upper_left']['x'] + $this->chart['length'], 'y' => $this->chart['upper_left']['y'], );
		$this->chart['middle_right'] = array( 'x' => $this->chart['upper_right']['x'], 'y' => $this->chart['middle_left']['y'], );
		$this->chart['lower_right'] = array( 'x' => $this->chart['upper_right']['x'], 'y' => $this->chart['lower_left']['y'], );
		$this->chart['legend_pen']['x'] = $this->chart['upper_left']['x'];
		$this->chart['legend_pen']['y'] = $this->chart['upper_left']['y'];
		
		$this->image = imagecreatetruecolor( $this->canvas['width'], $this->canvas['height'] );
		/// allocate standard colors
		$black = imagecolorallocate( $this->image, 0, 0, 0 );
		$gray = imagecolorallocate( $this->image, 204, 204, 204 ); 
		$red = imagecolorallocate( $this->image, 238, 36, 36 );
		$green = imagecolorallocate( $this->image, 7, 152, 0 );
		$blue = imagecolorallocate( $this->image, 74, 126, 187 ); 
		$white = imagecolorallocate( $this->image, 255, 255, 255 );
		$yellow = imagecolorallocate( $this->image, 255, 255, 0 );
		$brown = imagecolorallocate( $this->image, 127, 0, 0 );
		$pink = imagecolorallocate( $this->image, 255, 0, 255 );
		$this->pen_colors = array('black' => $black, 'gray' => $gray, 'red' => $red, 'green' => $green, 'blue' => $blue, 'white' => $white, 'yellow' => $yellow, 'brown' => $brown, 'pink' => $pink);

		/// chart background
		if ( !isset( $args[0]['img_background'] ) || empty( $args[0]['img_background'] ) || !array_key_exists( $args[0]['img_background'], $this->pen_colors ) ) { 
			$this->canvas['img_background'] = 'gray'; 
		} else {
			$this->canvas['img_background'] = $args[0]['img_background'];
		}
		imagefilledrectangle($this->image, 0, 0, $this->canvas['width'], $this->canvas['height'], $this->pen_colors[$this->canvas['img_background']]);
		/// chart area
		if ( !isset( $args[0]['chart_background'] ) || empty( $args[0]['chart_background'] ) || !array_key_exists( $args[0]['chart_background'], $this->pen_colors ) ) { 
			$this->canvas['chart_background'] = 'white';
		} else {
			$this->canvas['chart_background'] = $args[0]['chart_background'];
		}
		imagefilledrectangle($this->image, $this->chart['upper_left']['x'], $this->chart['upper_left']['y'], $this->chart['lower_right']['x'], $this->chart['lower_right']['y'], $this->pen_colors[$this->canvas['chart_background']] );
		
		/// add additional axes, if any are specified
		if ( count( $args ) > 1 ) {
			for ( $i = 1, $j = 0; ( $i < $this->n_axes || $i < count( $args ) - 1 ); $i += 2, $j++ ) {
				if ( isset( $args[$i] ) && is_array( $args[$i] ) && !empty( $args[$i] ) ) { 
					$this->x_axis[$j] = $args[$i]; 
                    if ( !isset( $args[$i]['min'] ) || !isset( $args[$i]['max'] ) ) throw new ChartException( $this->errors['110'] );
                    if ( !isset( $args[$i]['y_intersect'] ) ) throw new ChartException( $this->errors['112'] );
                    if ( $args[$i]['y_intersect'] > $args[$i]['max'] || $args[$i]['y_intersect'] < $args[$i]['min'] ) throw new ChartException( $this->errors['114'] );
                    if ( $args[$i]['major_interval'] > abs( $args[$i]['max'] ) + abs( $args[$i]['min'] ) ) throw new ChartException( $this->errors['116'] );
                    if ( $args[$i]['minor_interval_count'] == 0 ) throw new ChartException( $this->errors['118'] );
                    if ( !array_key_exists( $args[$i]['axis_color'], $this->pen_colors ) ) throw new ChartException( $this->errors['120'] );
                    if ( isset( $args[$i]['major_grid_color'] ) && !array_key_exists( $args[$i]['major_grid_color'], $this->pen_colors ) ) throw new ChartException( $this->errors['122'] );
                    if ( isset( $args[$i]['minor_grid_color'] ) && !array_key_exists( $args[$i]['minor_grid_color'], $this->pen_colors ) ) throw new ChartException( $this->errors['124'] );
                    if ( isset( $args[$i]['major_grid_style'] ) && !array_key_exists( $args[$i]['major_grid_style'], $this->line_prototypes ) ) throw new ChartException( $this->errors['126'] );
                    if ( isset( $args[$i]['minor_grid_style'] ) && !array_key_exists( $args[$i]['minor_grid_style'], $this->line_prototypes ) ) throw new ChartException( $this->errors['126'] );
                    if ( isset( $args[$i]['categories'] ) && !is_array( $args[$i]['categories'] ) ) throw new ChartException( $this->errors['134'] );
                    if ( !isset( $args[$i]['precision'] ) || empty( $args[$i]['precision'] ) ) $this->x_axis[$j]['precision'] = $this->default['precision_x'];
				}
				if ( isset( $args[$i + 1] ) && is_array( $args[$i + 1] ) && !empty( $args[$i + 1] ) ) {
					$this->y_axis[$j] = $args[$i + 1];
                    if ( !isset( $args[$i + 1]['min'] ) || !isset( $args[$i + 1]['max'] ) ) throw new ChartException( $this->errors['110'] );
                    if ( !isset( $args[$i + 1]['x_intersect'] ) ) throw new ChartException( $this->errors['130'] );
                    if ( $args[$i + 1]['x_intersect'] > $args[$i + 1]['max'] || $args[$i + 1]['x_intersect'] < $args[$i + 1]['min'] ) throw new ChartException( $this->errors['132'] );
                    if ( $args[$i + 1]['major_interval'] > abs( $args[$i + 1]['max'] ) + abs( $args[$i + 1]['min'] ) ) throw new ChartException( $this->errors['116'] );
                    if ( $args[$i + 1]['minor_interval_count'] == 0 ) throw new ChartException( $this->errors['118'] );
                    if ( !array_key_exists( $args[$i + 1]['axis_color'], $this->pen_colors ) ) throw new ChartException( $this->errors['120'] );
                    if ( isset( $args[$i + 1]['major_grid_color'] ) && !array_key_exists( $args[$i + 1]['major_grid_color'], $this->pen_colors ) ) throw new ChartException( $this->errors['122'] );
                    if ( isset( $args[$i + 1]['minor_grid_color'] ) && !array_key_exists( $args[$i + 1]['minor_grid_color'], $this->pen_colors ) ) throw new ChartException( $this->errors['124'] );
                    if ( isset( $args[$i + 1]['major_grid_style'] ) && !array_key_exists( $args[$i + 1]['major_grid_style'], $this->line_prototypes ) ) throw new ChartException( $this->errors['126'] );
                    if ( isset( $args[$i + 1]['minor_grid_style'] ) && !array_key_exists( $args[$i + 1]['minor_grid_style'], $this->line_prototypes ) ) throw new ChartException( $this->errors['126'] );
                    if ( isset( $args[$i + 1]['categories'] ) && !is_array( $args[$i + 1]['categories'] ) ) throw new ChartException( $this->errors['134'] );
                    if ( !isset( $args[$i + 1]['precision'] )) $this->y_axis[$j]['precision'] = $this->default['precision_y'];
				}
			}
		}
		
		/// calculate units per pixes ('upp') and chart origins for each axis pair
		foreach ( $this->x_axis as $key => $row ) {
			$this->x_axis[$key]['upp'] = abs( $row['max'] - $row['min'] ) / $this->chart['length'];
		
		}
		foreach ( $this->y_axis as $key => $row ) {
			$this->y_axis[$key]['upp'] = abs( $row['max'] - $row['min'] ) / $this->chart['height'];
		}
	
		/// create default color table
		$this->depth_table['band'] = ( abs( $this->depth_table['min'] ) + abs( $this->depth_table['max'] ) ) / ( $this->depth_table['d'] * 2 );
		$inc = ( 255 * 4 ) / ( $this->depth_table['d'] * 2 );
		/// assign starting color in the color table
		$color_table[0] = array('r' => 0, 'g' => 0, 'b' => 255);
		$this->pen_colors[0] = imagecolorallocate ($this->image, $color_table[0]['r'], $color_table[0]['g'], $color_table[0]['b'] );
		$color_step = array ('r' => 0, 'g' => 0, 'b' => $inc);

		for ( $i = $this->depth_table['min'] + 1 * $this->depth_table['band'], $row = 1; $i <= $this->depth_table['max']; $i += $this->depth_table['band'], $row++ ) {

			$color_table[$row] = array ( 'r' => (int) ( $color_table[$row - 1]['r'] + $color_step['r'] ), 'g' => (int) ( $color_table[$row - 1]['g'] + $color_step['g'] ), 'b' => (int) ( $color_table[$row - 1]['b'] + $color_step['b'] ) );
			
			/// color-wheel: Green-Yellow-Red-Black
			if ( $color_table[$row]['b'] > 255 ) { // && $color_table[$row]['r'] <= 0
				$color_table[$row]['g'] += $color_table[$row]['b'] - 255;
				$color_table[$row]['b'] = 255;
				$color_step = array ('r' => 0, 'g' => $inc, 'b' => 0);
			}
			if ( $color_table[$row]['g'] > 255 ) { // && $color_table[$row]['r'] <= 0
				$color_table[$row]['b'] -= $color_table[$row]['g'] - 255;
				$color_table[$row]['g'] = 255;
				$color_step = array ('r' => 0, 'g' => 0, 'b' => -$inc);
			}
			if ( $color_table[$row]['b'] < 0 ) { // && $color_table[$row]['r'] > 255
				$color_table[$row]['r'] -= $color_table[$row]['b'];
				$color_table[$row]['b'] = 0;
				$color_step = array ('r' => $inc, 'g' => 0, 'b' => 0);
			}
			if ( $color_table[$row]['r'] > 255 ) { //$color_table[$row]['g'] > 255 &&
				$color_table[$row]['g'] -= $color_table[$row]['r'] - 255;
				$color_table[$row]['r'] = 255;
				$color_step = array ('r' => 0, 'g' => -$inc, 'b' => 0);
			}
			if ( $color_table[$row]['g'] < 0 ) { // && $color_table[$row]['r'] > 255
				$color_table[$row]['r'] += $color_table[$row]['g'];
				$color_table[$row]['g'] = 0;
				$color_step = array ('r' => -$inc, 'g' => 0, 'b' => 0);
			}
			if ( $color_table[$row]['r'] < 0 ) { // && $color_table[$row]['g'] <= 0 
				$color_table[$row]['g'] = 0;
				$color_table[$row]['r'] = 0;
				$color_step = array ('r' => 0, 'g' => 0, 'b' => 0);
			}
			
			$this->pen_colors[$row] = imagecolorallocate( $this->image, $color_table[$row]['r'], $color_table[$row]['g'], $color_table[$row]['b'] );
			
		}
		
		/// draw grids, tick marks and axes: x_axis
		foreach ( $this->x_axis as $key => $row ) {
			if ( $row['show'] ) {
				// inflate line styles for major and minor grid. Axis lines are always drawn with simple solid lines.
				if ( isset( $row['major_grid_style'] ) ): $line['prototype']['major_grid'] = $this->line_prototypes[$row['major_grid_style']]; else: $line['prototype']['major_grid'] = $this->line_prototypes['dash']; endif;
				if ( isset( $row['major_grid_color'] ) ): $line['color']['major_grid'] = $this->pen_colors[$row['major_grid_color']]; else: $line['color']['major_grid'] = $this->pen_colors['black']; endif;
				if ( isset( $row['major_grid_scale'] ) ): $line['scale']['major_grid'] = $row['major_grid_scale']; else: $line['scale']['major_grid'] = $this->default['line_scale']; endif;
				$line['style']['major_grid'] = $this->get_line_style( array( $line['prototype']['major_grid'], $line['color']['major_grid'], $line['scale']['major_grid'], ) );
				if ( isset( $row['minor_grid_style'] ) ): $line['prototype']['minor_grid'] = $this->line_prototypes[$row['minor_grid_style']]; else: $line['prototype']['minor_grid'] = $this->line_prototypes['dash']; endif;
				if ( isset( $row['minor_grid_color'] ) ): $line['color']['minor_grid'] = $this->pen_colors[$row['minor_grid_color']]; else: $line['color']['minor_grid'] = $this->pen_colors['black']; endif;
				if ( isset( $row['minor_grid_scale'] ) ): $line['scale']['minor_grid'] = $row['minor_grid_scale']; else: $line['scale']['minor_grid'] = $this->default['line_scale']; endif;
				$line['style']['minor_grid'] = $this->get_line_style( array( $line['prototype']['minor_grid'], $line['color']['minor_grid'], $line['scale']['minor_grid'], ) );
				// fill in table for font (printing of values).
				if ( isset( $row['font_size'] ) ): $font['size'] = $row['font_size']; else: $font['size'] = $this->default['font_size_x']; endif;
				if ( isset( $row['precision'] ) ): $font['precision'] = $row['precision']; else: $font['precision'] = $this->default['precision_x']; endif;
				if ( isset( $row['font_angle'] ) ): $font['angle'] = $row['font_angle']; else: $font['angle'] = 0; endif; 
				if ( isset( $row['print_offset_major'] ) ): $font['offset_major'] = $row['print_offset_major']; else: $font['offset_major'] = $row['major_tick_size']; endif;
				if ( isset( $row['print_offset_minor'] ) ): $font['offset_minor'] = $row['print_offset_minor']; else: $font['offset_minor'] = $row['minor_tick_size']; endif;
				
				$minor_tick = $row['major_interval'] / $row['minor_interval_count'];
				for ( $i = $row['min']; $i <=  $row['max']; $i += $minor_tick ) {
					// translate current position to 'paper space' (pixels)
					$anchors['grid']['start'] = $this->units_to_pxls( array( $i, $this->y_axis[$key]['min'], $key, $key, ) );
					$anchors['grid']['end'] = $this->units_to_pxls( array( $i, $this->y_axis[$key]['max'], $key, $key, ) );
					$anchors['tick']['start'] = $this->units_to_pxls( array( $i, $this->y_axis[$key]['x_intersect'], $key, $key, ) );
					if ( $i % $row['major_interval'] == 0 ) { 
						if ( isset( $row['show_major_grid'] ) && $row['show_major_grid'] === TRUE ) { 
							imagesetstyle( $this->image, $line['style']['major_grid'] );
							imageline( $this->image, $anchors['grid']['start'][0], $anchors['grid']['start'][1], $anchors['grid']['end'][0], $anchors['grid']['end'][1], IMG_COLOR_STYLED );
						}
						/// draw major ticks
						imageline( $this->image, $anchors['tick']['start'][0], $anchors['tick']['start'][1], $anchors['tick']['start'][0], $anchors['tick']['start'][1] + $row['major_tick_size'], $this->pen_colors[$row['axis_color']] );
						/// type text for major value
						if ( isset( $row['show_major_values'] ) && $row['show_major_values'] === TRUE ) {
							if ( isset( $row['categories'][$i] ) && !empty( $row['categories'][$i]) ) {
								$text = $row['categories'][$i];
							} else {
								$text = sprintf( "%.{$font['precision']}f", $i );
							}
							imagettftext( $this->image, $font['size'], $font['angle'], $anchors['tick']['start'][0] +
                                                      $font['size'] / 2, $anchors['tick']['start'][1] + $row['major_tick_size'] +
                                                      $font['offset_major'], $this->pen_colors[$row['axis_color']], $this->canvas['ttf_font'], $text );
						}
					} else { // draw minor grid
						if ( isset( $row['show_minor_grid'] ) && $row['show_minor_grid'] === TRUE ) {
								imagesetstyle( $this->image, $line['style']['minor_grid'] );
								imageline( $this->image, $anchors['grid']['start'][0], $anchors['grid']['start'][1], $anchors['grid']['end'][0], $anchors['grid']['end'][1], IMG_COLOR_STYLED );
						} 
						/// draw minor ticks
						imageline( $this->image, $anchors['tick']['start'][0], $anchors['tick']['start'][1], $anchors['tick']['start'][0], $anchors['tick']['start'][1] + $row['minor_tick_size'], $this->pen_colors[$row['axis_color']] );
						/// type text for major value
						if ( isset( $row['show_minor_values'] ) && $row['show_minor_values'] === TRUE ) {
							if ( isset( $row['categories'][$i] ) && !empty( $row['categories'][$i] ) ) {
								$text = $row['categories'][$i];
							} else {
								$text = sprintf( "%.{$font['precision']}f", $i );
							}
							imagettftext( $this->image, $font['size'], $font['angle'], $anchors['tick']['start'][0], $anchors['tick']['start'][1] + $row['minor_tick_size'] + $font['offset_minor'], $this->pen_colors[$row['axis_color']], $this->canvas['ttf_font'], $text );
						}

					}
				}
				/// draw line for the axis
				$anchors['axis']['start'] = $this->units_to_pxls( array( $row['min'], $this->y_axis[$key]['x_intersect'], $key, $key ) );
				$anchors['axis']['end'] = $this->units_to_pxls( array( $row['max'], $this->y_axis[$key]['x_intersect'], $key, $key, ) );
				imageline( $this->image, $anchors['axis']['start'][0], $anchors['axis']['start'][1], $anchors['axis']['end'][0], $anchors['axis']['end'][1], $this->pen_colors[$row['axis_color']] );
			}
		}
		
		/// draw grids, tick marks and axes: y_axis
		foreach ( $this->y_axis as $key => $row ) {
			if ( $row['show'] ) {
				// inflate line styles for major and minor grid. Axis lines are always drawn with simple solid lines.
				if ( isset( $row['major_grid_style'] ) ): $line['prototype']['major_grid'] = $this->line_prototypes[$row['major_grid_style']]; else: $line['prototype']['major_grid'] = $this->line_prototypes['dash']; endif;
				if ( isset( $row['major_grid_color'] ) ): $line['color']['major_grid'] = $this->pen_colors[$row['major_grid_color']]; else: $line['color']['major_grid'] = $this->pen_colors['black']; endif;
				if ( isset( $row['major_grid_scale'] ) ): $line['scale']['major_grid'] = $row['major_grid_scale']; else: $line['scale']['major_grid'] = $this->default['line_scale']; endif;
				$line['style']['major_grid'] = $this->get_line_style( array( $line['prototype']['major_grid'], $line['color']['major_grid'], $line['scale']['major_grid'], ) );
				if ( isset( $row['minor_grid_style'] ) ): $line['prototype']['minor_grid'] = $this->line_prototypes[$row['minor_grid_style']]; else: $line['prototype']['minor_grid'] = $this->line_prototypes['dash']; endif;
				if ( isset( $row['minor_grid_color'] ) ): $line['color']['minor_grid'] = $this->pen_colors[$row['minor_grid_color']]; else: $line['color']['minor_grid'] = $this->pen_colors['black']; endif;
				if ( isset( $row['minor_grid_scale'] ) ): $line['scale']['minor_grid'] = $row['minor_grid_scale']; else: $line['scale']['minor_grid'] = $this->default['line_scale']; endif;
				$line['style']['minor_grid'] = $this->get_line_style( array( $line['prototype']['minor_grid'], $line['color']['minor_grid'], $line['scale']['minor_grid'], ) );
				// fill in table for font (printing of values).
				if ( isset( $row['font_size'] ) ): $font['size'] = $row['font_size']; else: $font['size'] = $this->default['font_size_y']; endif;
				if ( isset( $row['precision'] ) ): $font['precision'] = $row['precision']; else: $font['precision'] = $this->default['precision_y']; endif;
				if ( isset( $row['font_angle'] ) ): $font['angle'] = $row['font_angle']; else: $font['angle'] = 0; endif; 
				if ( isset( $row['print_offset_major'] ) ): $font['offset_major'] = $row['print_offset_major']; else: $font['offset_major'] = $row['major_tick_size']; endif;
				if ( isset( $row['print_offset_minor'] ) ): $font['offset_minor'] = $row['print_offset_minor']; else: $font['offset_minor'] = $row['minor_tick_size']; endif;
				
				$minor_tick = $row['major_interval'] / $row['minor_interval_count'];
				for ( $i = $row['min']; $i <=  $row['max']; $i += $minor_tick ) {
					// translate current position to 'paper space' (pixels)
					$anchors['grid']['start'] = $this->units_to_pxls( array( $this->x_axis[$key]['min'], $i, $key, $key, ) );
					$anchors['grid']['end'] = $this->units_to_pxls( array( $this->x_axis[$key]['max'], $i, $key, $key, ) );
					$anchors['tick']['start'] = $this->units_to_pxls( array( $this->x_axis[$key]['y_intersect'], $i, $key, $key, ) );
					//$this->result['i'][] = $i;
					//$this->result['major_interval'] = $row['major_interval'];
					if ( $i % $row['major_interval'] == 0 ) { 
						if ( isset( $row['show_major_grid'] ) && $row['show_major_grid'] === TRUE ) { 
							imagesetstyle( $this->image, $line['style']['major_grid'] );
							imageline( $this->image, $anchors['grid']['start'][0], $anchors['grid']['start'][1], $anchors['grid']['end'][0], $anchors['grid']['end'][1], IMG_COLOR_STYLED );
						} 
						/// draw major ticks
						imageline( $this->image, $anchors['tick']['start'][0], $anchors['tick']['start'][1], $anchors['tick']['start'][0] + $row['major_tick_size'], $anchors['tick']['start'][1], $this->pen_colors[$row['axis_color']] );
						/// type text for major value
						if ( isset( $row['show_major_values'] ) && $row['show_major_values'] === TRUE ) {
							if ( isset( $row['categories'][$i] ) && !empty( $row['categories'][$i] ) ) {
								$text = $row['categories'][$i];
							} else {
								$text = $text = ($font['precision'] > 0)? sprintf( "%.{$font['precision']}f", $i ) : sprintf(
                                  "%d", $i );
							}
							imagettftext( $this->image, $font['size'], $font['angle'], $anchors['tick']['start'][0] + $row['major_tick_size'] + $font['offset_major'], $anchors['tick']['start'][1] + $font['size'] / 2, $this->pen_colors[$row['axis_color']], $this->canvas['ttf_font'], $text );
						}

					} else { // draw minor grid
						if ( isset( $row['show_minor_grid'] ) && $row['show_minor_grid'] === TRUE ) {
							imagesetstyle( $this->image, $line['style']['minor_grid'] );
							imageline( $this->image, $anchors['grid']['start'][0], $anchors['grid']['start'][1], $anchors['grid']['end'][0], $anchors['grid']['end'][1], IMG_COLOR_STYLED );
						} 
						/// draw minor ticks
						imageline( $this->image, $anchors['tick']['start'][0], $anchors['tick']['start'][1], $anchors['tick']['start'][0] + $row['minor_tick_size'], $anchors['tick']['start'][1], $this->pen_colors[$row['axis_color']] );
						/// type text for minor value
						if ( isset( $row['show_minor_values'] ) && $row['show_minor_values'] === TRUE ) {
							if ( isset( $row['categories'][$i] ) && !empty( $row['categories'][$i] ) ) {
								$text = $row['categories'][$i];
							} else {
								$text = ($font['precision'] > 0)? sprintf( "%.{$font['precision']}f", $i ) : sprintf(
								  "%d", $i );
							}
							imagettftext( $this->image, $font['size'], $font['angle'], $anchors['tick']['start'][0] + $row['minor_tick_size'] + $font['offset_minor'], $anchors['tick']['start'][1] + $font['size'] / 2, $this->pen_colors[$row['axis_color']], $this->canvas['ttf_font'], $text );
						}

					}
				}
				/// draw line for the axis
				$anchors['axis']['start'] = $this->units_to_pxls( array( $this->x_axis[$key]['y_intersect'], $row['min'], $key, $key ) );
				$anchors['axis']['end'] = $this->units_to_pxls( array( $this->x_axis[$key]['y_intersect'], $row['max'], $key, $key, ) );
				imageline( $this->image, $anchors['axis']['start'][0], $anchors['axis']['start'][1], $anchors['axis']['end'][0], $anchors['axis']['end'][1], $this->pen_colors[$row['axis_color']] );
			}
		}
	}


    /**
     *
     * @public add_line_series( @args = array( 'series' => $series, ['x_axis' => 0, ] ['y_axis' => 0, ] ['color' => 'red', ] ['style' => 'dash', ] ['scale' => 1, ] ['show_values' => TRUE, ] ) )
     *
     * Draws simple lines for the array of values contained in $series. The series is drawn against x and y axes defined by indexes 'x_axis', 'y_axis' and may have values typed along the line itself.
     *    Placement of values is regulated by 'minor_tick_size'. Values are placed at end of each line segment. Values are omitted if end of the line segment falls onto an axis.
     *
     * @param $args ['series'] Array of values keyed by x_axis[<index>]['min'] and ['max']. Causes error if not specified.
     * @param $args ['x_axis'], $args['y_axis'] (optional) Integer values corresponding to the index of the x- and y-axis against which to plot values. Indexes of 0 are used if none specified.
     * @param $args ['color'] (optional) Color name as defined by keys of colors in $this->pen_colors. Defaults to black if not specified.
     * @param $args ['style'] (optional) Name of the line style as defined by keys of $this->line_prototypes. Defaults to 'default' if not specified.
     * @param $args ['scale'] (optional) Line scale. Defaults to 1 if none specified.
     * @param $args ['show_values'] (optional) Boolean flag to show values next to each point on the line. Placement of values is regulated by 'minor_tick_size' for the specifed y_axis.
     *
     * @throws ChartException
     */
	public function add_line_series( $args ) {
        if ( !isset( $args['series'] ) || empty( $args['series'] ) || !is_array( $args['series'] ) ) throw new ChartException( $this->errors['136'] );
        $n = count( $args['series'] );
        reset( $args['series'] ); /**< makes sure array pointer is set to beginning of the array for those cases when $args['series'] is passed by reference */
        if ( $n < 2 ) throw new ChartException( $this->errors['138'] );
        if ( !isset( $args['x_axis'] ) || empty( $args['x_axis'] ) ) $args['x_axis'] = 0;
        if ( !array_key_exists( $args['x_axis'], $this->x_axis ) ) throw new ChartException( $this->errors['140'] );
        if ( !isset( $args['y_axis'] ) || empty( $args['y_axis'] ) ) $args['y_axis'] = 0;
        if ( !array_key_exists( $args['y_axis'], $this->y_axis ) ) throw new ChartException( $this->errors['152'] );

        if ( !array_key_exists( $args['color'], $this->pen_colors ) ) $args['color'] = $this->pen_colors['black'];
        if ( !isset( $args['style'] ) || empty( $args['style'] ) || !array_key_exists( $args['style'], $this->line_prototypes ) ) $args['style'] = 'default';
        if ( !isset( $args['scale'] ) || empty( $args['scale'] ) ) $args['scale'] = 1;
        if ( $args['scale'] == 0 ) throw new ChartException( $this->errors['142'] );
        if ( !isset( $args['show_values'] ) || empty( $args['show_values'] ) ) $args['show_values'] = FALSE;

		$font['size'] = $this->y_axis[$args['y_axis']]['font_size'];
		$font['angle'] = $this->y_axis[$args['y_axis']]['font_angle'];
		$font['offset_x'] = $this->y_axis[$args['y_axis']]['minor_tick_size'];
		$font['offset_y'] = $this->y_axis[$args['x_axis']]['minor_tick_size'];
		$font['precision'] = $this->y_axis[$args['y_axis']]['precision'];
		$font['color'] = $this->pen_colors[$args['color']];
		
		$line['prototype'] = $this->line_prototypes[$args['style']];
		$line['color'] = $this->pen_colors[$args['color']];
		$line['scale'] = $args['scale'];
		
		for ( $i = 1; $i < $n; $i++ ) {
			/// get values for start and end of the line, translate into pixel coordinates
			$line['start'] = each( $args['series'] );
			$line['end'] = each( $args['series'] );
			$k = ( $line['end']['value'] - $line['start']['value'] ) / ( $line['end']['key'] - $line['start']['key'] );
			
			/// check for chart boundaries and chop line starts or ends at chart boundary
			/// ['start']['x'] < left chart boundary ['x']
			if ( $line['start']['key'] < $this->x_axis[$args['x_axis']]['min'] ) {
				$line['start']['key'] = $this->x_axis[$args['x_axis']]['min'];
				$line['start']['value'] = $k * ( $this->x_axis[$args['x_axis']]['min'] - $line['start'][0] ) + $line['start'][1];
			}
			/// ['start']['x'] > right chart boundary ['x']
			if ( $line['start']['key'] > $this->x_axis[$args['x_axis']]['max'] ) {
				$line['start']['key'] = $this->x_axis[$args['x_axis']]['max'];
				$line['start']['value'] = $k * ( $this->x_axis[$args['x_axis']]['max'] - $line['start'][0] ) + $line['start'][1];
			}
			/// ['start']['y'] < upper chart boundary ['y']
			if ( $line['start']['value'] > $this->y_axis[$args['y_axis']]['max'] ) {
				$line['start']['value'] = $this->y_axis[$args['y_axis']]['max'];
				if ( $k <> 0 ) {
					$line['start']['key'] = ( $this->y_axis[$args['y_axis']]['max'] - $line['start'][1] ) / $k + $line['start'][0];
				}
			}
			/// ['start']['y'] > lower chart boundary ['y']
			if ( $line['start']['value'] < $this->y_axis[$args['y_axis']]['min'] ) {
				$line['start']['value'] = $this->y_axis[$args['y_axis']]['min'];
				if ( $k <> 0 ) {
					$line['start']['key'] = ( $this->y_axis[$args['y_axis']]['min'] - $line['start'][1] ) / $k + $line['start'][0];
				}
			}
			/// ['end']['x'] < left chart boundary ['x']
			if ( $line['end']['key'] < $this->x_axis[$args['x_axis']]['min'] ) {
				$line['end']['key'] = $this->x_axis[$args['x_axis']]['min'];
				$line['end']['value'] = $k * ( $this->x_axis[$args['x_axis']]['min'] - $line['start'][0] ) + $line['start'][1];
			}
			/// ['end']['x'] > right chart boundary ['x']
			if ( $line['end']['key'] > $this->x_axis[$args['x_axis']]['max'] ) {
				$line['end']['key'] = $this->x_axis[$args['x_axis']]['max'];
				$line['end']['value'] = $k * ( $this->x_axis[$args['x_axis']]['max'] - $line['start'][0] ) + $line['start'][1];
			}
			/// ['end']['y'] < upper chart boundary ['y']
			if ( $line['end']['value'] > $this->y_axis[$args['y_axis']]['max'] ) {
				$line['end']['value'] = $this->y_axis[$args['y_axis']]['max'];
				if ( $k <> 0 ) { 
					$line['end']['key'] = ( $this->y_axis[$args['y_axis']]['max'] - $line['start'][1] ) / $k + $line['start'][0];
				}
			}
			/// ['end']['y'] > lower chart boundary ['y']
			if ( $line['end']['value'] < $this->y_axis[$args['y_axis']]['min'] ) {
				$line['end']['value'] = $this->y_axis[$args['y_axis']]['min'];
				if ( $k <> 0 ) {
					$line['end']['key'] = ( $this->y_axis[$args['y_axis']]['min'] - $line['start'][1] ) / $k + $line['start'][0];
				}
			}

			/// transfer line coords to "chart space"
			$anchors['line']['start'] = $this->units_to_pxls( array( $line['start']['key'], $line['start']['value'], $args['x_axis'], $args['y_axis'], ) );
			$anchors['line']['end'] = $this->units_to_pxls( array( $line['end']['key'], $line['end']['value'], $args['x_axis'], $args['y_axis'], ) );
			
			/// construct linestyle
			$line['style'] = $this->get_line_style( array( $line['prototype'], $line['color'], $line['scale'], ) );
			imagesetstyle( $this->image, $line['style'] );
			imageline( $this->image, $anchors['line']['start'][0], $anchors['line']['start'][1], $anchors['line']['end'][0], $anchors['line']['end'][1], IMG_COLOR_STYLED );
			/// type values
			if ( $args['show_values'] === TRUE ) { 
				/// do not type values if already got to y_intersect or if original starts and ends are outside of chart boundaries.
				if ( $line['end']['key'] != $this->x_axis[$args['x_axis']]['y_intersect'] && $line['end'][0] < $this->x_axis[$args['x_axis']]['max'] && $line['end'][1] < $this->y_axis[$args['y_axis']]['max'] ) { 
					$text = sprintf( "%.{$font['precision']}f", $line['end']['value'] );
					imagettftext( $this->image, $font['size'], $font['angle'], $anchors['line']['end'][0] + $font['offset_x'], $anchors['line']['end'][1] - $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
				}
			}
			
		prev( $args['series'] );
		
		} // end for()
	
	} // end function()


    /**
     *
     * @public add_candlestick_series( @args = array( 'open' => $open, 'high' => $high, 'low' => $low, 'close' => $close 'x_axis' => 0, 'y_axis' => 0,
     * 'color_up' => 'green', 'color_down' => 'red', 'advanced' => TRUE, 'transparency' = 50, 'show_values' => TRUE, ) )
     *
     * Draws candlesticks for the given OHLC data.
     * OHLC arrays must be keyed to the specified x_axis min and max values. Make sure array of your categories if specified when instantiating the chart has same keys as the OHLC arrays.
     *
     * @param $args ['open'], ['high'], ['low'], ['close'] Arrays of values keyed by x_axis[<index>]['min'] and ['max']. Causes error if not specified.
     * @param $args ['x_axis'], $args['y_axis'] (optional) Integer values corresponding to the index of the x- and y-axis against which to plot values. Indexes of 0 are used if none specified.
     * @param $args ['color_up'], $args['color_down'] (optional) colors specified in $this->pen_table for the up and down candles. Uses standard 'green' and 'red' values if not specified.
     * @param['advanced'] (optional) Turns on advanced features such as:
     *    - ...
     *   - ...
     * @param $args ['transparency'] (optional) Transparency level for the candlesticks: 0 - opaque, 100 - invisible. Default = 0.
     * @param $args ['show_values'] (optional) Bool flag to show values next to each candle. Placement of values is regulated by 'minor_tick_size' for the specifed y_axis. Number of decimal digits is regulated by the 'precision' setting of the correponding y_axis.
     *
     * @throws ChartException
     */
	public function add_candlestick_series( $args ) {
        if ( !isset( $args['open'] ) || empty( $args['open'] ) || !is_array( $args['open'] ) ) throw new ChartException( $this->errors['144'] );
        if ( !isset( $args['high'] ) || empty( $args['high'] ) || !is_array( $args['high'] ) ) throw new ChartException( $this->errors['146'] );
        if ( !isset( $args['low'] ) || empty( $args['low'] ) || !is_array( $args['low'] ) ) throw new ChartException( $this->errors['148'] );
        if ( !isset( $args['close'] ) || empty( $args['close'] ) || !is_array( $args['close'] ) ) throw new ChartException( $this->errors['150'] );
        if ( !isset( $args['x_axis'] ) || empty( $args['x_axis'] ) ) $args['x_axis'] = 0;
        if ( !isset( $args['y_axis'] ) || empty( $args['y_axis'] ) ) $args['y_axis'] = 0;
        if ( !array_key_exists( $args['x_axis'], $this->x_axis ) ) throw new ChartException( $this->errors['140'] );
        if ( !array_key_exists( $args['y_axis'], $this->y_axis ) ) throw new ChartException( $this->errors['152'] );
        if ( !isset( $args['color_up'] ) || !array_key_exists( $args['color_up'], $this->pen_colors )  ) $args['color_up'] = 'green';
        if ( !isset( $args['color_down'] ) || !array_key_exists( $args['color_down'], $this->pen_colors )  ) $args['color_down'] = 'red';
        if ( !isset( $args['advanced'] ) || empty( $args['advanced'] ) ) $args['advanced'] = FALSE;
        if ( !isset( $args['transparency'] ) || empty( $args['transparency'] ) ) {
            $args['transparency'] = 0;
        } elseif ( $args['transparency'] > 100 ) {
            $args['transparency'] = 100;
        }
        if ( isset( $args['show_values'] ) && !empty( $args['show_values'] ) && $args['show_values'] === TRUE ) {
            $args['show_values'] = TRUE;
            $font['size'] = $this->y_axis[$args['y_axis']]['font_size'];
            $font['angle'] = $this->y_axis[$args['y_axis']]['font_angle'];
            $font['offset_x'] = $this->y_axis[$args['y_axis']]['minor_tick_size'];
            $font['offset_y'] = $this->y_axis[$args['x_axis']]['minor_tick_size'];
            $font['precision'] = $this->y_axis[$args['y_axis']]['precision'];
            $font['color'] = $this->pen_colors[$this->y_axis[$args['y_axis']]['axis_color']];
        } else {
            $args['show_values'] = FALSE;
        }

		/// get initial readings of RGB color components in order to apply transparancy
		$candle['prev_high'] = -1;
		$candle['prev_low'] = -1;
		$candle['width'] = 1 / $this->x_axis[$args['x_axis']]['upp'] * 0.80;
		$candle['color_up'] = $this->apply_transparency( array( $args['color_up'], $args['transparency'] ) );
		$candle['color_down'] = $this->apply_transparency( array( $args['color_down'], $args['transparency'] ) );
		//$this->result = $args;
		foreach ( $args['open'] as $key => $open ) {
			if ( $key <= $this->x_axis[$args['x_axis']]['max'] && $key >= $this->x_axis[$args['x_axis']]['min'] ) {
				$candle['lower_shadow']['start'] = $this->units_to_pxls( array( $key, $args['low'][$key], $args['x_axis'], $args['y_axis'] ) );
				$candle['upper_shadow']['end'] = $this->units_to_pxls( array( $key, $args['high'][$key], $args['x_axis'], $args['y_axis'] ) );
				if ( $args['close'][$key] >= $args['open'][$key] ) {
					$candle['color'] = $candle['color_up'];
					$candle['filled'] = ( $args['close'][$key] > $candle['prev_high'] )? TRUE : FALSE;
					$candle['body']['start'] = $this->units_to_pxls( array( $key, $args['open'][$key], $args['x_axis'], $args['y_axis'] ) );
					$candle['upper_shadow']['start'] = $this->units_to_pxls( array( $key, $args['close'][$key], $args['x_axis'], $args['y_axis'] ) );
					if ( $args['show_values'] ) {
						$text = sprintf( "%.{$font['precision']}f", $args['open'][$key] );
						imagettftext( $this->image, $font['size'], $font['angle'], $candle['body']['start'][0] + $candle['width'] / 2 + $font['offset_x'], $candle['body']['start'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
						$text = sprintf( "%.{$font['precision']}f", $args['close'][$key] );
						imagettftext( $this->image, $font['size'], $font['angle'], $candle['upper_shadow']['start'][0] + $candle['width'] / 2 + $font['offset_x'], $candle['upper_shadow']['start'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
					}
					
				} else {
					$candle['color'] =$candle['color_down'];
					$candle['filled'] = ( $args['close'][$key] < $candle['prev_low'] )? TRUE : FALSE;
					$candle['body']['start'] = $this->units_to_pxls( array( $key, $args['close'][$key], $args['x_axis'], $args['y_axis'] ) );
					$candle['upper_shadow']['start'] = $this->units_to_pxls( array( $key, $args['open'][$key], $args['x_axis'], $args['y_axis'] ) );
					if ( $args['show_values'] ) {
						$text = sprintf( "%.{$font['precision']}f", $args['close'][$key] );
						imagettftext( $this->image, $font['size'], $font['angle'], $candle['body']['start'][0] + $candle['width'] / 2 + $font['offset_x'], $candle['body']['start'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
						$text = sprintf( "%.{$font['precision']}f", $args['open'][$key] );
						imagettftext( $this->image, $font['size'], $font['angle'], $candle['upper_shadow']['start'][0] + $candle['width'] / 2 + $font['offset_x'], $candle['upper_shadow']['start'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
					}
				}
				/// print candlestick
				imageline( $this->image, $candle['lower_shadow']['start'][0], $candle['lower_shadow']['start'][1], $candle['body']['start'][0], $candle['body']['start'][1], $candle['color'] );
				if ( $candle['filled'] ) {
					imagefilledrectangle( $this->image, $candle['body']['start'][0] - $candle['width'] / 2, $candle['body']['start'][1], $candle['upper_shadow']['start'][0] + $candle['width'] / 2, $candle['upper_shadow']['start'][1], $candle['color'] );
				} else {
					imagerectangle( $this->image, $candle['body']['start'][0] - $candle['width'] / 2, $candle['body']['start'][1], $candle['upper_shadow']['start'][0] + $candle['width'] / 2, $candle['upper_shadow']['start'][1], $candle['color'] );
				}
				imageline( $this->image, $candle['upper_shadow']['start'][0], $candle['upper_shadow']['start'][1], $candle['upper_shadow']['end'][0], $candle['upper_shadow']['end'][1], $candle['color'] );
				/// print low and high values
				if ( $args['show_values'] ) {
					$text = sprintf( "%.{$font['precision']}f", $args['low'][$key] );
					imagettftext( $this->image, $font['size'], $font['angle'], $candle['lower_shadow']['start'][0] + $font['offset_x'], $candle['lower_shadow']['start'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text );
					$text = sprintf( "%.{$font['precision']}f", $args['high'][$key] );
					imagettftext( $this->image, $font['size'], $font['angle'], $candle['upper_shadow']['end'][0] + $font['offset_x'], $candle['upper_shadow']['end'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text );
				}
				$candle['prev_high'] = $args['high'][$key];
				$candle['prev_low'] = $args['low'][$key];
			} // end if ()
			
		}
		
	}


    /**
     *
     * @public add_xbar_series( @args = array( 'values' => $volume, 'x_axis' => 0, 'y_axis' => 0, 'color_up' => 'green', 'color_down' => 'red',
     *   'advanced' => TRUE, 'transparency' = 50, 'show_values' => TRUE, ) )
     *
     * Draws vertical bars on x_axis for the given 'values'.
     * Values array must be keyed to the specified x_axis min and max values. Make sure array of your categories if specified when instantiating the chart has same keys as the values array.
     *
     * @param $args ['values'] Array of values keyed by x_axis[<index>]['min'] and ['max']. Causes error if not specified.
     * @param $args ['x_axis'], $args['y_axis'] (optional) Integer values corresponding to the index of the x- and y-axis against which to plot values. Indexes of 0 are used if none specified.
     * @param $args ['color_up'], $args['color_down'] (optional) colors specified in $this->pen_table for the bars that are above or below x_axis. Uses standard 'green' and 'red' values if not specified.
     * @param['advanced'] (optional) Turns on advanced features such as:
     *    - ...
     * @param $args ['transparency'] (optional) Transparency level for the candlesticks: 0 - opaque, 100 - invisible.
     * @param $args ['show_values'] (optional) Bool flag to show values next to each bar. Placement of values is regulated by 'minor_tick_size' for the specifed y_axis. Number of decimal digits is regulated by the 'precision' setting of the correponding y_axis.
     *
     * @throws ChartException
     */
	public function add_xbar_series( $args ) {
        if ( !isset( $args['values'] ) || empty( $args['values'] ) || !is_array( $args['values'] ) ) throw new ChartException( $this->errors['154'] );
        if ( !isset( $args['x_axis'] ) || empty( $args['x_axis'] ) ) $args['x_axis'] = 0;
        if ( !isset( $args['y_axis'] ) || empty( $args['y_axis'] ) ) $args['y_axis'] = 0;
        if ( !array_key_exists( $args['x_axis'], $this->x_axis ) ) throw new ChartException( $this->errors['140'] );
        if ( !array_key_exists( $args['y_axis'], $this->y_axis ) ) throw new ChartException( $this->errors['152'] );
        if ( !isset( $args['color_up'] ) || !array_key_exists( $args['color_up'], $this->pen_colors )  ) $args['color_up'] = 'green';
        if ( !isset( $args['color_down'] ) || !array_key_exists( $args['color_down'], $this->pen_colors )  ) $args['color_down'] = 'red';
        if ( !isset( $args['advanced'] ) || empty( $args['advanced'] ) ) $args['advanced'] = FALSE;
        if ( !isset( $args['transparency'] ) || empty( $args['transparency'] ) ) {
            $args['transparency'] = 0;
        } elseif ( $args['transparency'] > 100 ) {
            $args['transparency'] = 100;
        }
        if ( isset( $args['show_values'] ) && !empty( $args['show_values'] ) && $args['show_values'] === TRUE ) {
            $args['show_values'] = TRUE;
            $font['size'] = $this->y_axis[$args['y_axis']]['font_size'];
            $font['angle'] = $this->y_axis[$args['y_axis']]['font_angle'];
            $font['offset_x'] = $this->y_axis[$args['y_axis']]['minor_tick_size'];
            $font['offset_y'] = $this->y_axis[$args['x_axis']]['minor_tick_size'];
            $font['precision'] = $this->y_axis[$args['y_axis']]['precision'];
            $font['color'] = $this->pen_colors[$this->y_axis[$args['y_axis']]['axis_color']];
        } else {
            $args['show_values'] = FALSE;
        }

		/// get initial readings of RGB color components in order to apply transparancy
		//$candle['prev_high'] = -1;
		//$candle['prev_low'] = -1;
		$bar['width'] = 1 / $this->x_axis[$args['x_axis']]['upp'] * 0.80;
		$bar['color_up'] = $this->apply_transparency( array( $args['color_up'], $args['transparency'] ) );
		$bar['color_down'] = $this->apply_transparency( array( $args['color_down'], $args['transparency'] ) );
		//$this->result = $args;
		foreach ( $args['values'] as $key => $value ) {
			if ( $key <= $this->x_axis[$args['x_axis']]['max'] && $key >= $this->x_axis[$args['x_axis']]['min'] ) {
				if ( $value > $this->y_axis[$args['y_axis']]['max'] ) $value = $this->y_axis[$args['y_axis']]['max'];
				if ( $value < $this->y_axis[$args['y_axis']]['min'] ) $value = $this->y_axis[$args['y_axis']]['min'];
				$bar['start'] = $this->units_to_pxls( array( $key, $this->y_axis[$args['y_axis']]['x_intersect'], $args['x_axis'], $args['y_axis'] ) );
				$bar['end'] = $this->units_to_pxls( array( $key, $value, $args['x_axis'], $args['y_axis'] ) );
				if ( $args['values'][$key] >= $this->y_axis[$args['y_axis']]['x_intersect'] ) {
					$bar['color'] = $bar['color_up'];
					//$bar['filled'] = ( $args['close'][$key] > $bar['prev_high'] )? TRUE : FALSE;
					//$bar['body']['start'] = $this->units_to_pxls( array( $key, $args['value'][$key], $args['x_axis'], $args['y_axis'] ) );
					//$bar['upper_shadow']['start'] = $this->units_to_pxls( array( $key, $args['close'][$key], $args['x_axis'], $args['y_axis'] ) );
					if ( $args['show_values'] ) {
						//$text = sprintf( "%.{$font['precision']}f", $value );
						//imagettftext( $this->image, $font['size'], $font['angle'], $bar['start'][0] + $bar['width'] / 2 + $font['offset_x'], $bar['body']['start'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
						$text = sprintf( "%.{$font['precision']}f", $value );
						imagettftext( $this->image, $font['size'], $font['angle'], $bar['end'][0] + $bar['width'] / 2 + $font['offset_x'], $bar['end'][1] - $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
					}
					
				} else {
					$bar['color'] =$bar['color_down'];
					//$bar['filled'] = ( $args['close'][$key] < $bar['prev_low'] )? TRUE : FALSE;
					//$bar['body']['start'] = $this->units_to_pxls( array( $key, $args['close'][$key], $args['x_axis'], $args['y_axis'] ) );
					//$bar['upper_shadow']['start'] = $this->units_to_pxls( array( $key, $args['value'][$key], $args['x_axis'], $args['y_axis'] ) );
					if ( $args['show_values'] ) {
						//$text = sprintf( "%.{$font['precision']}f", $args['close'][$key] );
						//imagettftext( $this->image, $font['size'], $font['angle'], $bar['body']['start'][0] + $bar['width'] / 2 + $font['offset_x'], $bar['body']['start'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
						$text = sprintf( "%.{$font['precision']}f", $value );
						imagettftext( $this->image, $font['size'], $font['angle'], $bar['end'][0] + $bar['width'] / 2 + $font['offset_x'], $bar['end'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
					}

				}
				/// print barstick
				//imageline( $this->image, $bar['lower_shadow']['start'][0], $bar['lower_shadow']['start'][1], $bar['body']['start'][0], $bar['body']['start'][1], $bar['color'] );
				//if ( $bar['filled'] ) {
					imagefilledrectangle( $this->image, $bar['start'][0] - $bar['width'] / 2, $bar['start'][1], $bar['end'][0] + $bar['width'] / 2, $bar['end'][1], $bar['color'] );
				//} else {
				//	imagerectangle( $this->image, $bar['body']['start'][0] - $bar['width'] / 2, $bar['body']['start'][1], $bar['upper_shadow']['start'][0] + $bar['width'] / 2, $bar['upper_shadow']['start'][1], $bar['color'] );
				//}
				//imageline( $this->image, $bar['upper_shadow']['start'][0], $bar['upper_shadow']['start'][1], $bar['upper_shadow']['end'][0], $bar['upper_shadow']['end'][1], $bar['color'] );
				/// print low and high values
				//if ( $args['show_values'] ) {
				//	$text = sprintf( "%.{$font['precision']}f", $args['low'][$key] );
				//	imagettftext( $this->image, $font['size'], $font['angle'], $bar['lower_shadow']['start'][0] + $font['offset_x'], $bar['lower_shadow']['start'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text );
				//	$text = sprintf( "%.{$font['precision']}f", $args['high'][$key] );
				//	imagettftext( $this->image, $font['size'], $font['angle'], $bar['upper_shadow']['end'][0] + $font['offset_x'], $bar['upper_shadow']['end'][1] + $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text );
				//}
				$bar['prev_value'] = $args['values'][$key];
				//$bar['prev_low'] = $args['low'][$key];
			} // end if ()
			
		}
		
	}


    /**
     *
     * @public add_ybar_series( @args = array( 'values' => $volume, 'x_axis' => 0, 'y_axis' => 0, 'color_up' => 'green', 'color_down' => 'red',
     *   'advanced' => TRUE, 'transparency' = 50, 'show_values' => TRUE, ) )
     *
     * Draws horizontal bars on y_axis for the given 'values'.
     * Values array must be keyed to the specified y_axis min and max values. Make sure array of your categories if specified when instantiating the chart has same keys as the values array.
     *
     * @param $args ['values'] Array of values keyed by x_axis[<index>]['min'] and ['max']. Causes error if not specified.
     * @param $args ['x_axis'], $args['y_axis'] (optional) Integer values corresponding to the index of the x- and y-axis against which to plot values. Indexes of 0 are used if none specified.
     * @param $args ['color_up'], $args['color_down'] (optional) colors specified in $this->pen_table for the bars that are to the left or to the right of the y_axis. Uses standard 'green' and 'red' values if not specified.
     * @param['advanced'] (optional) Turns on advanced features such as:
     *    - ...
     * @param $args ['transparency'] (optional) Transparency level for the candlesticks: 0 - opaque, 100 - invisible.
     * @param $args ['show_values'] (optional) Bool flag to show values next to each bar. Placement of values is regulated by 'minor_tick_size' for the specifed y_axis. Number of decimal digits is regulated by the 'precision' setting of the correponding y_axis.
     *
     * @throws ChartException
     */
	public function add_ybar_series( $args ) {
        if ( !isset( $args['values'] ) || empty( $args['values'] ) || !is_array( $args['values'] ) ) throw new ChartException( $this->errors['154'] );
        if ( !isset( $args['x_axis'] ) || empty( $args['x_axis'] ) ) $args['x_axis'] = 0;
        if ( !isset( $args['y_axis'] ) || empty( $args['y_axis'] ) ) $args['y_axis'] = 0;
        if ( !array_key_exists( $args['x_axis'], $this->x_axis ) ) throw new ChartException( $this->errors['140'] );
        if ( !array_key_exists( $args['y_axis'], $this->y_axis ) ) throw new ChartException( $this->errors['152'] );
        if ( !isset( $args['color_up'] ) || !array_key_exists( $args['color_up'], $this->pen_colors )  ) $args['color_up'] = 'green';
        if ( !isset( $args['color_down'] ) || !array_key_exists( $args['color_down'], $this->pen_colors )  ) $args['color_down'] = 'red';
        if ( !isset( $args['advanced'] ) || empty( $args['advanced'] ) ) $args['advanced'] = FALSE;
        if ( !isset( $args['transparency'] ) || empty( $args['transparency'] ) ) {
            $args['transparency'] = 0;
        } elseif ( $args['transparency'] > 100 ) {
            $args['transparency'] = 100;
        }
        if ( isset( $args['show_values'] ) && !empty( $args['show_values'] ) && $args['show_values'] === TRUE ) {
            $args['show_values'] = TRUE;
            $font['size'] = $this->x_axis[$args['x_axis']]['font_size'];
            $font['angle'] = $this->x_axis[$args['x_axis']]['font_angle'];
            $font['offset_x'] = $this->x_axis[$args['x_axis']]['minor_tick_size'];
            $font['offset_y'] = $this->x_axis[$args['x_axis']]['minor_tick_size'];
            $font['precision'] = $this->x_axis[$args['x_axis']]['precision'];
            $font['color'] = $this->pen_colors[$this->x_axis[$args['x_axis']]['axis_color']];
        } else {
            $args['show_values'] = FALSE;
        }

		$bar['width'] = 1 / $this->y_axis[$args['y_axis']]['upp'] * 0.80;
		$bar['color_up'] = $this->apply_transparency( array( $args['color_up'], $args['transparency'] ) );
		$bar['color_down'] = $this->apply_transparency( array( $args['color_down'], $args['transparency'] ) );
		foreach ( $args['values'] as $key => $value ) {
			if ( $key <= $this->y_axis[$args['y_axis']]['max'] && $key >= $this->y_axis[$args['y_axis']]['min'] ) {
				if ( $value > $this->x_axis[$args['x_axis']]['max'] ) $value = $this->x_axis[$args['x_axis']]['max'];
				if ( $value < $this->x_axis[$args['x_axis']]['min'] ) $value = $this->x_axis[$args['x_axis']]['min'];
				$bar['start'] = $this->units_to_pxls( array( $this->x_axis[$args['x_axis']]['y_intersect'], $key, $args['x_axis'], $args['y_axis'] ) );
				$bar['end'] = $this->units_to_pxls( array( $value, $key, $args['x_axis'], $args['y_axis'] ) );
				if ( $args['values'][$key] >= $this->x_axis[$args['x_axis']]['y_intersect'] ) {
					$bar['color'] = $bar['color_up'];
					if ( $args['show_values'] ) {
						$text = sprintf( "%.{$font['precision']}f", $value );
						imagettftext( $this->image, $font['size'], $font['angle'], $bar['end'][0] + $font['offset_x'], $bar['end'][1] - $bar['width'] / 2 - $font['offset_y'] , $font['color'], $this->canvas['ttf_font'], $text ); 
					}
					
				} else {
					$bar['color'] =$bar['color_down'];
					if ( $args['show_values'] ) {
						$text = sprintf( "%.{$font['precision']}f", $value );
						imagettftext( $this->image, $font['size'], $font['angle'], $bar['end'][0] - $font['offset_x'], $bar['end'][1] - $bar['width'] / 2 - $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text ); 
					}

				}
				imagefilledrectangle( $this->image, $bar['start'][0], $bar['start'][1] + $bar['width'] / 2, $bar['end'][0], $bar['end'][1] - $bar['width'] / 2, $bar['color'] );
				$bar['prev_value'] = $args['values'][$key];
			} // end if ()
			
		}
		
	}


    /**
     *
     * @public add_symbol_series( @args = array( 'values' => (array) $symbol_values, [ 'x_axis' => 0, ] ['position' => (array) &$some_series | (num) $y_value,  ] ['pos_offset' => -10, ] ['y_axis' => 0, ]
     * ['shape' => 'circle', ] ['color' => 'red' | COLOR_INDEX | COLOR_PENS, ] ['prefix' => some_text, ] ['show_values' => TRUE | FALSE, ] ['precision' => 2, ] ) )
     *
     * The symbol series is additional annotation added to the chart in the form of geometric shapes, text and values. Positioning of the symbols can be associated with a data series passed by reference
     *  in this function's array.
     *
     * @param $args ['values'] array of values for the symbols. Must be keyed by min and max values of the given x_axis.
     * @param $args ['x_axis'] (optional) index for x_axis to which to tie the symbols series. Default x_axis[0].
     * @param $args ['y_axis'] (optional) Index of y_axis to which the series is tied to or the y_level is tied to. Default is y_axis index 0.
     * @param $args ['position'] (optional) Tells where to position the symbols. Either near values of a given series or at at given y_axis value level. Default is y_axis level of x_intersect.
     * 'y_axis' index needs to be specified as well (see above). Also note that the chart does not "remember" which series was added to it, so the assigned series must be keyed between x_axis
     * min and max values.
     * @param $args ['pos_offset'] (optional) Offset in pixels from the specified 'position' series of the symbol's graphic. This setting does not affect playcement of text annotations net to the symbol. Can be positive or negative. This parameter is ignored if 'position' is given as a constant. If 'position' is given as a series, defaults to 'major_tick_size' of the specified y_axis.
     * @param $args ['shape'] (optional) Name of the shape to draw for the symbol. Defaults to none. Names of shapes must be those contained in $this->symbol_shapes.
     * @param $args ['color'] (optional) Either color name contained in $this->pen_colors, which would be used for entire symbols_series without regard to their values, or one of the following:
     *   (constant) COLOR_INDEX - use when the values of your symbols lie in the range specified by 'min' and 'max' values of $this->depth_table. Default range is -100 to 100. So, if the values of your symbols are in this range the colors will be matched to a linear scale from -100 to 100.
     *   (constant) COLOR_SERIES - use when you want to calibrate colors to the min and max values stored in the symbols values themselves. So, for example, if your symbols values are from 0 to 50, then the deep blue color would be assigned to 0 and hot red to 50.
     *   defaults to COLOR_SERIES.
     * @param $args ['prefix'] (optional) a text to be added as part of the symbol annotation. Text format (size, angle and font) would be taken from the referenced y_axis.
     * @param $args ['show_values'] (optional) Specifies whether to show symbol_values
     * @param $args ['precision'] (optional) Specifies number of decimal digits shown for each symbol_value. Default is 'precision' parameter for the given y_axis.
     * @param $args ['suffix'] (optional) a text to be added as part of the symbol annotation. Text format (size, angle and font) would be taken from the referenced y_axis.
     * @param $args ['transparency'] (optional) Transparency level: 0 - opaque, 100 - invisible. Default = 0.
     *
     * @throws ChartException
     */
	public function add_symbol_series( $args ) {
        if ( !isset( $args['values'] ) || empty( $args['values'] ) || !is_array( $args['values'] ) ) throw new ChartException( $this->errors['154'] );
        $symbol['min'] = min( $args['values'] );
        $symbol['max'] = max( $args['values'] );
        $symbol['band'] = ( $this->depth_table['max'] - $this->depth_table['min'] ) / ( $symbol['max'] - $symbol['min'] ) * $this->depth_table['band'];
        //var_dump( $this->depth_table ); exit();
        if ( !isset( $args['x_axis'] ) || empty( $args['x_axis'] ) || !is_numeric( $args['x_axis'] ) || !array_key_exists( $args['x_axis'], $this->x_axis ) ) $args['x_axis'] = 0;
        if ( !isset( $args['y_axis'] ) || empty( $args['y_axis'] ) || !is_numeric( $args['y_axis'] ) || !array_key_exists( $args['y_axis'], $this->y_axis ) ) $args['y_axis'] = 0;
        if ( !isset( $args['position'] ) || empty( $args['position'] ) ) $args['position'] = $this->y_axis[$args['y_axis']]['x_intersect'];
        if ( !isset( $args['pos_offset'] ) || empty( $args['pos_offset'] ) || !is_numeric( $args['pos_offset'] ) ) $args['pos_offset'] = $this->y_axis[$args['y_axis']]['major_tick_size'];
        if ( !isset( $args['shape'] ) || empty( $args['shape'] ) || !in_array( $args['shape'], $this->symbol_shapes ) ) $args['shape'] = FALSE;
        if ( !isset( $args['color'] ) || empty( $args['color'] ) ) {
            $args['color'] = COLOR_SERIES;
        } elseif( $args['color'] <> COLOR_INDEX || $args['color'] <> COLOR_SERIES ) {
            if ( !array_key_exists( $args['color'], $this->pen_colors ) ) $args['color'] = COLOR_SERIES;
        }
        if ( $args['color'] == COLOR_INDEX ) {
            if ( $symbol['min'] < $this->depth_table['min'] ) throw new ChartException( $this->errors['156'] );
            if ( $symbol['max'] > $this->depth_table['max'] ) throw new ChartException( $this->errors['158'] );				
        }
        if ( !isset( $args['prefix'] ) || empty( $args['prefix'] ) ) $args['prefix'] = '';
        if ( isset( $args['show_values'] ) && !empty( $args['show_values'] ) && $args['show_values'] === TRUE ) {
            $args['show_values'] = TRUE;
            $font['size'] = $this->y_axis[$args['y_axis']]['font_size'];
            $font['angle'] = $this->y_axis[$args['y_axis']]['font_angle'];
            $font['offset_x'] = $this->y_axis[$args['y_axis']]['minor_tick_size'];
            $font['offset_y'] = ( $args['pos_offset'] >= 0 )? abs( $this->y_axis[$args['y_axis']]['minor_tick_size'] ) : abs( $this->y_axis[$args['y_axis']]['minor_tick_size'] ) * (-1);
            $font['color'] = $this->pen_colors[$this->y_axis[$args['y_axis']]['axis_color']];
        } else {
            $args['show_values'] = FALSE; 
        }
        if ( !isset( $args['precision'] ) || empty( $args['precision'] ) || !is_numeric( $args['precision'] ) ) $args['precision'] = $this->y_axis[$args['y_axis']]['precision'];
        if ( !isset( $args['suffix'] ) || empty( $args['suffix'] ) ) $args['suffix'] = '';
        if ( !isset( $args['transparency'] ) || empty( $args['transparency'] ) || $args['transparency'] < 0 ) { 
            $args['transparency'] = 0;
        } elseif ( $args['transparency'] > 100 ) {
            $args['transparency'] = 100;
        }

		/// assign constant color if 'color' element is a word
		if ( !is_numeric( $args['color'] ) ) $symbol['color'] = $this->apply_transparency( array( $args['color'], $args['transparency'] ) );

		foreach ( $args['values'] as $key => $value ) {
			
			if ( $key <= $this->x_axis[$args['x_axis']]['max'] && $key >= $this->x_axis[$args['x_axis']]['min'] ) {
				/// calibrate color either by range stored in $this->depth_table
				if ( $args['color'] == COLOR_INDEX ) {
					$index = round( ( $value - $this->depth_table['min'] ) * $this->depth_table['band'], 0);
					$symbol['color'] = $this->apply_transparency( array( $index, $args['transparency'] ) );
				} else { /// ...or by the min and max values of the symbol series itself
					$index = round( ( $value - $symbol['min'] ) * $symbol['band'], 0);
					$symbol['color'] = $this->apply_transparency( array( $index, $args['transparency'] ) );
				}
				/// see if needs to be positioned by a given series
				if ( is_array( $args['position'] ) ) { 
					$symbol['y_position'] = $args['position'][$key] + $args['pos_offset'] * $this->y_axis[$args['y_axis']]['upp'];
				} else { /// ...or at a constant
					$symbol['y_position'] = $args['position'];
				}
				/// Figure symbol pixel coordinates.
				$anchors['origin'] = $this->units_to_pxls( array( $key, $symbol['y_position'], $args['x_axis'], $args['y_axis'], ) );
				$anchors['width'] = $this->x_axis[$args['x_axis']]['major_tick_size'];
				$anchors['height'] = $this->y_axis[$args['y_axis']]['major_tick_size'];

				switch ( $args['shape'] ) {
					case 'circle':
						imagefilledellipse( $this->image, $anchors['origin']['x'], $anchors['origin']['y'], $anchors['width'], $anchors['height'], $symbol['color'] );
					break;
					case 'square':
						imagefilledrectangle( $this->image, $anchors['origin']['x'] - $anchors['width'] / 2, $anchors['origin']['y'] - $anchors['height'] / 2, $anchors['origin']['x'] + $anchors['width'] / 2, $anchors['origin']['y'] + $anchors['height'] / 2, $symbol['color'] );
					break;
					case 'triangle-up':
						$vertices = array( $anchors['origin']['x'] - $anchors['width'] / 2, $anchors['origin']['y'] + $anchors['height'] / 2,
																$anchors['origin']['x'], $anchors['origin']['y'] - $anchors['height'] / 2, 
																$anchors['origin']['x'] + $anchors['width'] / 2, $anchors['origin']['y'] + $anchors['height'] / 2,
																);
						imagefilledpolygon( $this->image, $vertices, 3, $symbol['color'] );
					break;
					case 'triangle-down':
						$vertices = array( $anchors['origin']['x'] - $anchors['width'] / 2, $anchors['origin']['y'] - $anchors['height'] / 2,
																$anchors['origin']['x'] + $anchors['width'] / 2, $anchors['origin']['y'] - $anchors['height'] / 2,
																$anchors['origin']['x'], $anchors['origin']['y'] + $anchors['height'] / 2, 
																);
						imagefilledpolygon( $this->image, $vertices, 3, $symbol['color'] );
					break;
					
				} // end switch ( $args['shape'] )

				/// type symbol values
				if ( $args['show_values'] ) {
					$text = sprintf( "%s %-.{$args['precision']}f %s", $args['prefix'], $value, $args['suffix'] );
					imagettftext( $this->image, $font['size'], $font['angle'], $anchors['origin']['x'] + $font['offset_x'], $anchors['origin']['y'] - $font['offset_y'], $font['color'], $this->canvas['ttf_font'], $text );
				}
			} // end if( $key <=...
		}
	}

	
	/**
	* @public draw_rectangle( array( 'sx' => 10, 'sy' => 10, 'ex' => 50, 'ey' => 50, 'color' => 'red', ['transparency' => 90, ] ['x_axis' => 0, ] ['y_axis' => 0, ] ['hollow' => TRUE] ) )
	* 
	* Draws filled rectangle (default) with coordinates specified in units (not image pixels) for the given indeces of x_- and y_axes.
	* 
	*/
	public function draw_rectangle( $args ) {
        if ( !isset( $args['sx'] ) || !isset( $args['sy'] ) || !isset( $args['ex'] ) || !isset( $args['ey'] ) ) throw new ChartException( $this->errors['160'] );
        if ( !array_key_exists( $args['color'], $this->pen_colors ) ) throw new ChartException( $this->errors['162'] );
        if ( !isset( $args['transparency'] ) || empty( $args['transparency'] ) || $args['transparency'] < 0 ) { 
            $args['transparency'] = 0;
        } elseif ( $args['transparency'] > 100 ) {
            $args['transparency'] = 100;
        }
        if ( !isset( $args['x_axis'] ) || empty( $args['x_axis'] ) || !is_numeric( $args['x_axis'] ) || !array_key_exists( $args['x_axis'], $this->x_axis ) ) $args['x_axis'] = 0;
        if ( !isset( $args['y_axis'] ) || empty( $args['y_axis'] ) || !is_numeric( $args['y_axis'] ) || !array_key_exists( $args['y_axis'], $this->y_axis ) ) $args['y_axis'] = 0;
        if ( !isset( $args['hollow'] ) || empty( $args['hollow'] ) || $args['hollow'] == FALSE ) { $rectangle['hollow'] = FALSE; } else { $rectangle['hollow'] = TRUE; }

		$rectangle['color'] = $this->apply_transparency( array( $args['color'], $args['transparency'] ) );
		if ( $args['sx'] < $this->x_axis[$args['x_axis']]['min'] ) $args['sx'] = $this->x_axis[$args['x_axis']]['min'];
		if ( $args['sx'] > $this->x_axis[$args['x_axis']]['max'] ) $args['sx'] = $this->x_axis[$args['x_axis']]['max'];
		if ( $args['sy'] < $this->y_axis[$args['y_axis']]['min'] ) $args['sy'] = $this->y_axis[$args['y_axis']]['min'];
		if ( $args['sy'] > $this->y_axis[$args['y_axis']]['max'] ) $args['sy'] = $this->y_axis[$args['y_axis']]['max'];
		if ( $args['ex'] < $this->x_axis[$args['x_axis']]['min'] ) $args['ex'] = $this->x_axis[$args['x_axis']]['min'];
		if ( $args['ex'] > $this->x_axis[$args['x_axis']]['max'] ) $args['ex'] = $this->x_axis[$args['x_axis']]['max'];
		if ( $args['ey'] < $this->y_axis[$args['y_axis']]['min'] ) $args['ey'] = $this->y_axis[$args['y_axis']]['min'];
		if ( $args['ey'] > $this->y_axis[$args['y_axis']]['max'] ) $args['ey'] = $this->y_axis[$args['y_axis']]['max'];
		$rectangle['start'] = $this->units_to_pxls( array( $args['sx'], $args['sy'], $args['x_axis'], $args['y_axis'], ) );
		$rectangle['end'] = $this->units_to_pxls( array( $args['ex'], $args['ey'], $args['x_axis'], $args['y_axis'], ) );
		if ( !$rectangle['hollow'] ) { 
			imagefilledrectangle( $this->image, $rectangle['start']['x'], $rectangle['start']['y'], $rectangle['end']['x'], $rectangle['end']['y'], $rectangle['color'] );
		} else {
			imagerectangle( $this->image, $rectangle['start']['x'], $rectangle['start']['y'], $rectangle['end']['x'], $rectangle['end']['y'], $rectangle['color'] );
		}
	}
	
	
	/**
	* @public place_text( array( 'sx' => 10, 'sy' => 10, 'text' => 'text', ['vert_algn' => 'up' | 'center' | 'down' ]['color' => 'black', ] ['x_axis' => 0, ] ['y_axis' => 0, ] ['wordwrap' => 15, ] ['font_size'] => 12, ] ['font_angle' => 0, ] ['ttf_font' => 'path/']  ) )
	* 
	* Places text at specified sx and sy coordinates (units of specified indicies for axes as references, not pixels).
     * Text node is specified by sx and sy, and text is centered vertically around that node using 'vert_algn'
	* 
	* @params (optional) 'vert_align' Default is 'center'. Text will be centered vertically around text node, 'up' text node will be located in the upper left corner, 'down' text node will be located in the lower left corner.
	* @params (optional) 'color' color name must be specified in $this->pen_colors.
	* @params (optional) 'x_axis', 'y_axis' indeces of the axes contained in the instantiated chart object.
	* @params (optional) 'wordwrap' number of characters to wrap the text to. 0 or omitted parameter will not wrap the text. Line spacing will be set to $this->y_axis[$args['y_axis']]['minor_tick_size'].
	* @params (optional) 'font_size' in pixels. Will use $this->y_axis[$args['y_axis']]['font_size'] if not specified.
  * @params (optional) 'font_angle' angle in degrees, will use $this->y_axis[$args['y_axis']]['font_angle'] if not specified. Angle 0 is at 9 o'clock and increases clockwise.
	* @params (optional) 'ttf_font' path to the ttf font to use. Will use $this->canvas['ttf_font'] as default.
	*
	* @result Placed text on chart object. Returns an array with 8 elements representing four points making the bounding box of the text. The order of the points is lower left, lower right, upper right, upper left. The points are relative to the text regardless of the angle, so "upper left" means in the top left-hand corner when you see the text horizontally. Returns FALSE on error.
	* 
	*/
	public function place_text( $args ) {
		
        if ( !isset( $args['sx'] ) || !isset( $args['sy'] ) ) throw new ChartException( $this->errors['164'] );
        if ( !isset( $args['text'] ) || empty( $args['text'] ) ) throw new ChartException( $this->errors['166'] );
        if ( !isset( $args['vert_algn'] ) || empty( $args['vert_algn'] ) || $args['vert_algn'] == 'center' ) { $font['vert_algn'] = 0.5; } elseif ( $args['vert_algn'] == 'up' ) { $font['vert_algn'] = 1; } else { $font['vert_algn'] = 0; }
        if ( !isset( $args['color'] ) || empty( $args['color'] ) || !array_key_exists( $args['color'], $this->pen_colors ) ) { $font['color'] = $this->pen_colors['black']; } else { $font['color'] = $this->pen_colors[$args['color']]; }
        if ( !isset( $args['x_axis'] ) || empty( $args['x_axis'] ) || !is_numeric( $args['x_axis'] ) || !array_key_exists( $args['x_axis'], $this->x_axis ) ) $args['x_axis'] = 0;
        if ( !isset( $args['y_axis'] ) || empty( $args['y_axis'] ) || !is_numeric( $args['y_axis'] ) || !array_key_exists( $args['y_axis'], $this->y_axis ) ) $args['y_axis'] = 0;
        if ( !isset( $args['wordwrap'] ) || empty( $args['wordwrap'] ) || !is_numeric( $args['wordwrap'] ) || $args['wordwrap'] <= 0 ) { $font['wordwrap'] = FALSE; } else { $font['wordwrap'] = $args['wordwrap']; }
        if ( !isset( $args['font_size'] ) || empty( $args['font_size'] ) || !is_numeric( $args['font_size'] ) ) { $font['font_size'] = $this->y_axis[$args['y_axis']]['font_size']; } else { $font['font_size'] = $args['font_size']; }
        if ( !isset( $args['font_angle'] ) || empty( $args['font_angle'] ) ) { $font['font_angle'] = $this->y_axis[$args['y_axis']]['font_angle']; } else { $font['font_angle'] = $args['font_angle']; }
        if ( !isset( $args['ttf_font'] ) || empty( $args['ttf_font'] ) ) { $font['ttf_font'] = realpath( $this->canvas['ttf_font'] ); } else { $font['ttf_font'] = realpath( $args['ttf_font'] ); }
        
        
        //$args['sy'] -= $font['font_size'] * $font['vert_algn'];
        $anchors['text_node'] = $this->units_to_pxls( array( $args['sx'], $args['sy'], $args['x_axis'], $args['y_axis'], ) );
        $anchors['text_node']['y'] += $font['font_size'] * $font['vert_algn'];
        if ( $font['wordwrap'] ) {
            // Break it up into pieces 125 characters long
            $lines = explode( '|', wordwrap( $args['text'], $font['wordwrap'], '|', TRUE ) );
            //var_dump( wordwrap( $args['text'], $font['wordwrap'], '|', TRUE ) ); exit();
            //var_dump( $lines ); exit();
            $font['line_spacing'] = $this->y_axis[$args['y_axis']]['minor_tick_size'];
            foreach ($lines as $line) {
                $result = imagettftext( $this->image, $font['font_size'], $font['font_angle'], $anchors['text_node']['x'], $anchors['text_node']['y'], $font['color'], $font['ttf_font'], $line );
                $anchors['text_node']['y'] += $font['font_size'] + $font['line_spacing'];
            }
            //$result 
        } else {
            $result = imagettftext($this->image, $font['font_size'], $font['font_angle'], $anchors['text_node']['x'], $anchors['text_node']['y'], $font['color'], $font['ttf_font'], $args['text'] );
        }
        
        return $result;
	}


    /**
     *
     * @public save_chart( array( 'path', ) )
     *
     * Saves chart as .png file either under specified path, or under default location stored in $this->canvas['path'].
     *
     * @param $args ['path'] Path to file name of the chart. Must end with file name with extension of .png
     *
     * @result bool TRUE on success, bool FALSE on failure.
     *
     * @return bool|void
     * @throws ChartException
     */
	public function save_chart( $args = array() ) 
    {
        if ( !is_array( $args ) ) throw new ChartException( $this->errors['108'] );
        if ( isset( $args['path'] ) && is_string( $args['path'] ) ) { 
            $path_parts = pathinfo( $args['path'] );
            if ( is_dir( $path_parts['dirname'] ) ) { 
                $this->canvas['path'] = $args['path'];
            } else {
                throw new ChartException( $this->errors['104'] );
            }
        }
        
        return ImagePNG( $this->image, $this->canvas['path'] );
	}


    /**
     * private units_to_pxls( array( 0 => x, 1 => y, 2 => x_axis, 3 => y_axis, ) )
     *
     * Translates passed x and y values into pixel coordinates on the chart
     *
     * @param x_axis Index of $this->x_axis to use for pixel coordinate calculation.
     * @param y_axis
     *
     * @return array
     */
	private function units_to_pxls( $args ) { 
		$sx = (int) ( $this->chart['lower_left']['x'] + ( $args[0] - $this->x_axis[$args[2]]['min'] ) / $this->x_axis[$args[2]]['upp'] );
		$sy = (int) ( $this->chart['lower_left']['y'] - ( $args[1] - $this->y_axis[$args[3]]['min'] ) / $this->y_axis[$args[3]]['upp'] );
		return array( $sx, $sy, 'x' => $sx, 'y' => $sy, );
	}


    /**
     *
     * @private get_line_style( @args = array( $style, $color, $scale, ) ).
     *
     * Takes array of boolean values for the "skeleton" of the line style, which represents arrangements of dots and spaces, applies specified $color and $scale.
     *
     * @param array $args [0] $style array of boolean values representing the line structure. Example: array( 0 => array( 1 => 3 ), 1 => array( 0 => 3 ) ). Keys of the outer array simply enumerate elements. Keys of the inner arrays represent either "dot" (1) or "space" (0). Values are number of pixels.
     * @param int $args [1] $color value produced by the imagecolorallocate function.
     * @param float $args [2] $scale value of the line by which number of pixels of each $style element get multiplied.
     *
     * @result array $line_style Resulting array ready to be passed to the imagesetstyle function.
     *
     * @return array
     */
	private function get_line_style( $args ) {
		$line_style = array();
		foreach ( $args[0] as $line ) {
			$record = each( $line );
			for ( $i = 1; $i <= (int) $record['value'] * $args[2]; $i++ ) {
				if ( $record['key'] ) { 
					$line_style[] = $args[1];
				} else {
					$line_style[] = IMG_COLOR_TRANSPARENT;
				}
			}
		}
		return $line_style;
	}


    /**
     *
     * @private apply_transparency( @args = array( $color_name, $transparancy, ) ).
     *
     * Applies the value of $transparency to a $color_name that is contined in $this->pen_colors. This function uses imagecolorallocatealpha() to return the value of the color with transparency.
     *
     * @param array $args [0] $color_name Name of the color contained in keys of the $this->pen_colors array.
     * @param int $args [1] $transparency Value of transparancy to apply. 0 - no transparency (opaque color), 100 - full transparency (invisible).
     *
     * @result (int) $color_value as a result of the imagecolorallocatealpha function. FALSE on failure.
     *
     * @return false|int
     */
	private function apply_transparency( $args ) {
		$red = ( 16711680 & $this->pen_colors[$args[0]] ) / pow( 256, 2 );
		$green = ( 65280 & $this->pen_colors[$args[0]] ) / pow( 256, 1 );
		$blue = ( 255 & $this->pen_colors[$args[0]] ) / pow( 256, 0 );
		$alpha = 127 * $args[1] / 100;
		return imagecolorallocatealpha( $this->image, $red, $green, $blue, $alpha );
	}
}
