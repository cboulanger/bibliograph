<?php

/**
 * Original code from http://www.bin-co.com/php/scripts/classes/gd_image/
 * Enter description here ...
 * @var unknown_type
 */
define("IMAGE_FLIP_HORIZONTAL",	1);
define("IMAGE_FLIP_VERTICAL",	2);
define("IMAGE_FLIP_BOTH",		3);

/**
 * A Wrapper for GD library in PHP. GD must be installed in your system for this to work.
 * Example: $img = new Image('wheel.png');
 * 			$img->flip(1)->resize(120, 0)->save('wheel.jpg');
 */
class Image 
{
	private $file_name;
	private $info;
	private $width;
	private $height;
	private $image;
	private $org_image;

	/**
	 * Constructor
	 * @param string $image_file
	 */
	function Image($image_file) 
	{
		if(!function_exists('imagecreatefrompng')) return; //GD not available
		if(!file_exists($image_file) or !is_readable($image_file)) return;
		
		$this->file_name = $image_file;
		$img = getimagesize($image_file);

		//Create the image depending on what kind of file it is.
		switch($img['mime']) {
			case 'image/png' : $image = imagecreatefrompng($image_file); break;
			case 'image/jpeg': $image = imagecreatefromjpeg($image_file); break;
			case 'image/gif' : 
				$old_id = imagecreatefromgif($image_file); 
				$image  = imagecreatetruecolor($img[0],$img[1]); 
				imagecopy($image,$old_id,0,0,0,0,$img[0],$img[1]); 
				break;
			default: break;
		}
		$this->info		= $img;
		$this->width	= imagesx($image);
		$this->height	= imagesy($image);
		$this->image	= $this->org_image = $image;
	}
	
	/**
	 * Returns the image's mime type
	 * Enter description here ...
	 */
	public function getMimeType()
	{
		return $this->info['mime'];
	}
	
	/**
	 * Rotates the image to any direction using the given angle.
	 * Arguments: $angle - The rotation angle, in degrees.
	 * Example: $img = new Image("file.png"); $img->rotate(180); $img->show(); // Turn the image upside down.
	 */
	function rotate( $angle, $background=0 ) {
		if(!$this->image) return false;
		if(!$angle) return $this;
		
		$this->image = imagerotate($this->image, $angle, $background);
		return $this;
	}

	/**
	 * Mirrors the given image in the desired way.
	 * Arguments : $type - Direction of mirroring. This can be 1(Horizondal Flip), 2(Vertical Flip) or 3(Both Horizondal and Vertical Flip)
	 * Example: $img = new Image("file.png"); $img->flip(2); $img->show();
	 */
	function flip($type) {
		if(!$this->image) return false;
		if(!$type) return false;
		
		$imgdest= imagecreatetruecolor($this->width, $this->height);
		$imgsrc	= $this->image;
		$height	= $this->height;
		$width	= $this->width;

		switch( $type ) {
			//Mirroring direction
			case IMAGE_FLIP_HORIZONTAL:
			case 'h':
				for( $x=0 ; $x<$width ; $x++ )
					imagecopy($imgdest, $imgsrc, $width-$x-1, 0, $x, 0, 1, $height);
				break;

			case IMAGE_FLIP_VERTICAL:
			case 'v':
				for( $y=0 ; $y<$height ; $y++ )
					imagecopy($imgdest, $imgsrc, 0, $height-$y-1, 0, $y, $width, 1);
				break;

			case IMAGE_FLIP_BOTH:
			case 'b':
				for( $x=0 ; $x<$width ; $x++ )
					imagecopy($imgdest, $imgsrc, $width-$x-1, 0, $x, 0, 1, $height);

				$rowBuffer = imagecreatetruecolor($width, 1);
				for( $y=0 ; $y<($height/2) ; $y++ ) {
					imagecopy($rowBuffer, $imgdest  , 0, 0, 0, $height-$y-1, $width, 1);
					imagecopy($imgdest  , $imgdest  , 0, $height-$y-1, 0, $y, $width, 1);
					imagecopy($imgdest  , $rowBuffer, 0, $y, 0, 0, $width, 1);
				}

				imagedestroy( $rowBuffer );
				break;
			}
		
		$this->image = $imgdest;
		return $this;
	}
	
	/**
	 * Resize the image to an new size. Size can be specified in the arugments.
	 * @param int $width
	 * 		The width of the desired image. If 0, the function will automatically 
	 * 		calculate the width using the height ratio. This works only if you 
	 * 		set $keep_aspect_ratio = false.
	 * @param int $height 
	 * 		The height of the desired image. If 0, the function will automatically 
	 * 		calculate the value using the width ratio. This works only if you 
	 * 		set $keep_aspect_ratio = false.
	 * @param bool $keep_aspect_ratio
	 * 		If true, keep the aspect ration during resizing. I
	 * @param bool $use_resize
	 * 		If true, the function uses imagecopyresized() function instead of 
	 * 		imagecopyresampled(). Resize is faster but poduces poorer quality image. 
	 * 		Resample on the other hand is slower - but makes better images. Defaults
	 * 		to false, i.e. better quality is used by default.
	 */
	function resize( $width, $height, $keep_aspect_ratio=true, $use_resize = false) 
	{
		if( ! $this->image )
		{
			trigger_error("No image loaded"); 
		}
		if( ! $height and !$width ) 
		{
			trigger_error("height and width cannot both be 0.");
		}
		
		$old_height = $this->height;
		$old_width  = $this->width;
		
		/*
		 * keep aspect ration?
		 */
		if( $keep_aspect_ratio )
		{
			if( ! $height or !$width ) 
			{
				trigger_error("height and width cannot be 0.");
			}			
			if ( $old_width > $old_height ) 
			{
				$new_width  = $width;
				$new_height = $old_height * ( $height / $old_width );
			}
			if ( $old_width < $old_height ) 
			{
				$new_width  = $old_width * ( $width / $old_height );
				$new_height = $height;
			}
			if ( $old_width == $old_height ) 
			{
				$new_width  = $width;
				$new_height = $height;
			}
		}
		else 
		{
			/*
			 * If the width or height is 0, find the 
			 * correct ratio using the other value
			 */
			if( ! $height and $width)
			{
				 $new_height = $old_height * $width / $old_width; 
			}
			if( $new_height and ! $new_width) 
			{
				$new_width	= $old_width  * $height/ $old_height;
			}
		}			

		//Create the image
		$new_image = imagecreatetruecolor($new_width,$new_height);
		imagealphablending($new_image, true);
		
		if( $this->info['mime'] == 'image/png' )
		{
			imagesavealpha($new_image,true);	
		} 
		
		
		if ($use_resize) 
		{
			imagecopyresized($new_image, $this->image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);
		}
		else
		{
			imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);
		}
		
		$this->image = $new_image;
		return $this;
	}

	/** 
	 * Crops the given image from the ($from_x,$from_y) point to the ($to_x,$to_y) point.
	 * Arguments :$from_x - X coordinate from where the crop should start
	 *			  $from_y - Y coordinate from where the crop should start
	 *			  $to_x   - X coordinate from where the crop should end
	 *			  $to_y   - Y coordinate from where the crop should end
	 * Example: $img -> crop(250,200,400,250);
	 */
	function crop($from_x,$from_y,$to_x,$to_y) {
		if(!$this->image) return false;
		
		$height = $this->height;
		$width  = $this->width;

		$new_width  = $to_x - $from_x;
		$new_height = $to_y - $from_y;
		//Create the image
		$new_image = imagecreatetruecolor($new_width, $new_height);
		imagealphablending($new_image, false);
		imagecopy($new_image, $this->image, 0,0, $from_x,$from_y, $new_width, $new_height);
		$this->image = $new_image;
		
		return $this;
	}

	/**
	 * Save the image to the given file. You can use this function to convert image types to. Just specify the image format you want as the extension.
	 * Argument:$file_name - the file name to which the image should be saved to
	 * Returns: false if save operation fails.
	 * Example: $img->save("image.png"); 
	 * 			$image->save('file.jpg');
	 */
	function save($file_name, $destroy = true) {
		if(!$this->image) return false;
		
		$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
		
		switch($extension) {
			case 'png' : return imagepng($this->image, $file_name); break;
			case 'jpeg': 
			case 'jpg' : return imagejpeg($this->image, $file_name); break;
			case 'gif' : return imagegif($this->image, $file_name); break;
			default: break;
		}
		if($destroy) $this->destroy();
		return false;
	}

	/**
	 * Display the image and then destroy it.
	 * Example: $img->show();
	 */
	function show($destroy = true) {
		if(!$this->image) return false;
		
		header("Content-type: ".$this->info['mime']);
		switch($this->info['mime']) {
			case 'image/png' : imagepng($this->image); break;
			case 'image/jpeg': imagejpeg($this->image); break;
			case 'image/gif' : imagegif($this->image); break;
			default: break;
		}
		if($destroy) $this->destroy();
		
		return $this;
	}
	
	/**
	 * Discard any changes made to the image and restore the original state
	 */
	function restore() {
		$this->image = $this->org_image;
		return $this;
	}
	
	/**
	 * Destroy the image to save the memory. Do this after all operations are complete.
	 */
	function destroy() {
		 imagedestroy($this->image);
		 imagedestroy($this->org_image);
	}
}
