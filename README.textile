h1. DynResize

The whole concept arose from my needs to *dynamically resize* a big amount of images on a news server and on my blog. I wanted to create a universal system, which can satisfy all my demands – to keep the *original aspect ratio*, to *cache the output*, not to care about the whole resizing process. I wanted to follow the basic order of user's behavior – writing an article, uploading images to FTP, linking to several specific sizes of images without resizing them manually.

h2. Features

* Resizes images.
* Outputs JPEG images.
* Keeps the original aspect ratio. Does not deform the source images.
** Can either crop the image or add extra space on edges to keep the aspect ratio.
* Does not upscale the source — adds extra space on the edges (optional)
* Caches the output to save server resources.
* Detects changes within the same file path/name when caching is on.
* Customizable output
** You can define your own styles and types of output — using an XML file.
** Background color (used while cropping for the space on the edges).
* Customizable errors
** Error image
** Error background color
** Error lines showing (cross on the background)
** Error lines color
** Error lines radius (number of pixels from the edge where the lines end on)
* Can block resizing remote images (i. e. images from a different domain than @$_SERVER['SERVER_NAME']@).

h2. Licence

!http://i.creativecommons.org/l/by-sa/3.0/88x31.png!

DynResize by "Jan Kuča":http://jankuca.com/ is licensed under a "Creative Commons Attribution-Share Alike 3.0 Unported License":http://creativecommons.org/licenses/by-sa/3.0/

If you have any questions about the attribution, email me at jake[at]jakecooney.com.

h2. Requirements

* PHP 5
* GD Libraries v2

h2. Parameters


|_.  |_. Constant |_. Default value |_. Description |_. Since |
| string | DYNRESIZE_ROOT | ./ | Path to the images directory; MUST ends with /. | 1.0 |
| string | DYNRESIZE_STYLE | default | Selected style (set of types) | 1.0 |
| string | DYNRESIZE_STYLE_SOURCE_FILE | ./dynresize-styles.xml | Location of the styles/types XML file. | 1.1 |
| int | DYNRESIZE_QUALITY | 60 | JPEG quality level; Overrides the type value. The value can be a number from 0 (max compression) to 100 (max quality). | 1.0 |
| boolean | DYNRESIZE_CROP | true | Crop the source image to fit the original aspect ratio. If false, there will be stripes on edges. | 1.0 |
| string | DYNRESIZE_BGCOLOR | #000000 | Color of stripes on edges; Used only when the cropping is off. | 1.0 |
| boolean | DYNRESIZE_EXTERNAL | false | Allow resizing remote images | 1.0 |
| string | DYNRESIZE_EXTERNAL_CACHE | true | Allow caching remote images | 1.1 |
| string | DYNRESIZE_EXTERNAL_CACHEDIR | ./dynresize/cache/.remote/ | Path to the directory for caching remote images. | 1.1 |
| string | DYNRESIZE_ERROR_IMAGE |   | Path to the error image; If not set or does not exist, no image is used. | 1.0 |
| string | DYNRESIZE_ERROR_BGCOLOR | #FFEBEB | Hexadecimal code of background color while errors | 1.0 |
| boolean | DYNRESIZE_ERROR_LINES | true | Show lines while errors (cross under the error image) | 1.0 |
| string | DYNRESIZE_ERROR_LINECOLOR | #FDDBD8 | Hexadecimal code of line color while errors | 1.0 |
| int | DYNR | ESIZE_ERROR_RADIUS | 0 | Number of pixels from the edge where the lines end on | 1.0 |
| boolean | DYNRESIZE_CACHE | true | Cache the output | 1.0 |
| string | DYNRESIZE_CACHEDIR | ./dynresize/cache/ | Cache the output	1.3 |
| boolean | DYNRESIZE_UPSCALE | false | Allow upscaling | 1.4.1 |
| boolean | DYNRESIZE_ERROR | (not defined) | Disable the engine; If defined, the output will be an error. | 1.0 |

h3. GET parameters

|_. Variable |_. Description |_. Since | |
| string | path | Path to the image (relative to the predefined images directory) | 1.0 |
| string | type | Predefined type of image; Types are defined in a styles XML file. | 1.0 |
| boolean | lines | Show lines while errors; Overrides the predefined value. The value can be 0 or 1. | 1.0 |
| int | radius | Number of pixels from image edge where the lines end on; Overrides the predefined value. | 1.0 |
| int | quality | JPEG quality level; Overrides the predefined value. The value can be a number from 0 (max compression) to 100 (max quality). | 1.0 |
| int | w | Width of the output (in pixels); Does not override the predefined type value. | 1.0 |
| int | h | Height of the output (in pixels); Does not override the predefined type value. | 1.0 |
| string | bgcolor | Equivalent to the DYNRESIZE_BGCOLOR constant; Overrides the predefined value. (Example: bgcolor=5F5F5F) | 1.2 |
| nocache | If set a fresh resize of the source image is forced. | 1.2 |

h2. Examples

* @dynresize.php?type=large&path=images/example.jpg@ – Resizes the images from @DYNRESIZE_ROOT/images/example.jpg@ and applies the type _large_ from the style defined in the config file.
* @dynresize.php?w=100&h=50&path=images/example.jpg@ – Resizes the images from @DYNRESIZE_ROOT/images/example.jpg@ to 100×50px image.
* @dynresize.php?w=100&path=images/example.jpg@ – Resizes the images from @DYNRESIZE_ROOT/images/example.jpg@ to 100×?px image. (The height is calculated from the original aspect ratio.)

h2. Implementation

DynResize runs as an external application, which really simplifies the implementation process.

# Download DynResize from this page.
# Upload the contents of the downloaded archive to your space.
# Open the configuration file (dynresize-config.php) and edit the parameters according to the table above.
# Open the styles definition file (dynresize-styles.xml) and add your specific style with the types you use on your website. It is recommended to keep the original default style in the file.
# Do not forget to specify your image direcotory.
# Now you have two different options how to link to your images:
#* dynresize.php?type=small&path=image.png
#* Use the provided .htaccess rewrite rule and link to your images as @image/small/image.png@
# If you allow caching, make sure the cache directory is writable for the script. In case it is not, change mode (CHMOD) to 777.
# Now, when everything you need is set up…
## Try uploading some image into the image directory.
## Run the script with setting up the requested type/size and the path to the image: dynresize.php?type=THE-REQUESTED-TYPE&path=THE-IMAGE-PATH
## If caching is on, there should be a new file created in the images directory. The filename should be THE-ORIGINAL-FILENAME.WIDTHxHEIGHT.jpg
## If the output is an image with dimensions corresponding with the type definition, everything is OK and you can start using DynResize on your website.

It would be nice if you placed a reference link to this page on your site. Please do not add a nofollow attribute ;-)