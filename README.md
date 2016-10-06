![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)

# DustPress
- Contributors: [devgeniem](https://github.com/devgeniem) / [Nomafin](https://github.com/Nomafin), [villesiltala](https://github.com/villesiltala)
- URL: http://www.dustpress.com
- Tags: dustpress, wordpress, dustjs, dust.js
- Requires at least: 4.2.0
- Tested up to: 4.5.2
- License: GPLv2 or later
- License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Description

A WordPress theme framework for writing template files with Dust.js templating engine and separate data models.

## Installation

We recommend that you install DustPress with Composer, but it is also possible to do it manually.

### Composer
Install with composer:

```
$ composer require devgeniem/dustpress
```

OR add it into your `composer.json`:

```json
{
  "require": {
    "devgeniem/dustpress": "*"
  }
}
```

DustPress supports Composer's autoload feature. If you have it enabled, you don't have to do anything else to use DustPress. If not, you need to require `dustpress.php` in your `functions.php`.

### Manually

- Clone this repository somewhere in your project and require the `dustpress.php` file in your `functions.php`.

## Usage

You need to call `dustpress();` in your `functions.php` to enable DustPress. It must be naturally be done after requiring the library itself if you haven't used Composer's autoload feature.

Within your theme there must be two directories called `models` and `partials` to use DustPress. Their purpose will be explained later in this file.

The basics of using DustPress are very simple. Unlike traditional WordPress theme development, DustPress relies on MVVM, or Model View ViewModel architecture in which fetching data and displaying it to the user are separated into different modules.

## File naming and locations

### Data models

Even though implementing an almost completely new development pattern to WordPress theme developers, DustPress still uses some of the WordPress core functions. The naming of the data models and view partials follow the naming conventions of traditional WordPress themes. The model for a single post should be named `single.php` etc.

In WordPress, your custom page templates could be named pretty much anything as long as you declare the name of the template in the comment section in the beginning of the file. This is the case in DustPress too, but the class name that you write for the model should follow a certain pattern. For example if you have a `Frontpage` template with a filename `page-frontpage.php`, your class should be named PageFrontpage. The class names are case sensitive. The same goes with custom content type singles, where a single `person` file should be named `single-person.php` and the class accordingly `SinglePerson`.

You still have to declare a name for the templates in the starting comment as you would have done in a traditional WordPress theme as well. This allows user to choose the template file to use with the page and points the DustPress core
to load the correct model when loading the page.

The models must be located in the `models` directory. They could, however, be arranged in any kind of subdirectory tree, so feel free to keep them in whatever structure you like. Note that WordPress also needs to find your template file in order it to work.

### Views

The Dust templates are the views of our design pattern. DustPress uses Geniem's fork of [DustPHP](https://github.com/devgeniem/dust-php) library for parsing the Dust templates.

All the data gathered and returned by the public functions of your models are automatically passed to the view. DustPress looks for the Dust templates in the `partials` directory under the root of the theme. Like models, they could be arranged in any kind of subdirectory hierarchy, so feel free to use whatever suits your needs.

By default the Dust template files follow the naming of the models. `single.php` should be paired with `single.dust`. This naming convention can be overwritten in your model by calling the `set_template()` function. In any of the public functions of the model write `$this->set_template("partial_name")` and it will be used instead of the default template. The `.dust` file extension is not needed.

## Data models

The data models of DustPress consist of a class named the same as the file but in CamelCase instead of hyphens. `page-frontpage.php` should have a class named `PageFrontpage` that extends the `\DustPress\Model` class:

```
<?php
/*
Template name: Frontpage
*/

class PageFrontpage extends \DustPress\Model {
  //
}
?>
```

### Autoconstructing and modular usage

As described above DustPress automatically locates your main model following the WordPress theme naming conventions and structure. The main model is loaded and constructed automatically. Lots of good stuff happen behind the scenes in the `__construct` method of the `Model` class. _Do not overwrite it without calling `parent::__construct();` in the beginning of your own constructor._

Alongside the autoloading you can use DustPress models in any modular use case you can come up with. One example would be building a custom API in a file called `api.php` containing a `Model` extending class called `API` _(no need to follow the naming convention since the class is not autoconstructed)_ running all kinds of public functions rendering multiple custom templates. Yes, with DustPress you can do Dust rendering anywhere within your WordPress project! [(see the example)]() [(power up your API with DustPressJS)]()

### Binding the data

DustPress has its own global data object that is passed to the view when everything is done and it is time to render the page. Binding data to the object is done via the `return` statements in publicly accessible functions. While autoloading the main model and its submodels, all public functions will automatically be run. If you have data you want to load inside a function and do not want to include it into the global data object, set the visibility of a function to `private` or `protected`.

```
public function last_posts() {
    $args = [ 'posts_per_page' => 3 ];
    return get_posts( $args );
}
```

DustPress data object holds a variety of other objects that are user defined models. For example if you have a frontpage with a header, a content block, a sidebar and a footer, the data object would look like this:

```
object(stdClass)#1 (5) {
  ["PageFrontpage"]=>
  object(stdClass)#2 (0) {
  }
  ["Header"]=>
  object(stdClass)#2 (0) {
  }
  ["Sidebar"]=>
  object(stdClass)#2 (0) {
  }
  ["Footer"]=>
  object(stdClass)#2 (0) {
  }
}
```

#### Submodels

Recurring elements like headers or footers should be created as submodels that can be included in any page. Submodels have their own models which are located in their own files inside the `models` directory. They are attached to the main model with the aforementioned `bind_sub()` method. The frontpage model could look like this:

```
<?php
/*
Template name: Frontpage
*/

class PageFrontpage extends \DustPress\Model {

  public function init() {
    $this->bind_sub("Header");
    $this->bind_sub("Sidebar");
    $this->bind_sub("Footer");
  }
}
?>
```

This code fetches all three models and binds their data to the global data hierarchy under the corresponding object. Notice that we have created a public function `init` which is automatically run by DustPress and therefore the submodels will be included. No `init` block will be created in the data tree since we do not return anything in our function.

Submodel bindings can be run anywhere in the model for example inside an `if` statement. Submodels work recursively, hence submodels can bind more submodels.

`bind_sub()` can also take a second parameter, an array of arguments to be passed to the submodel. It is then accessible in the submodel globally by calling `$this->get_args()`.

#### Binding the data

The actual passing of the data to inside the methods happens via user defined functions. DustPress runs through all public methods in the model and puts their return data to the global data object under current model's branch of the tree. It goes in a object named after the method.

```
public function SomeData() {
  return "This is data.";
}
```

If this code is located in our PageFrontpage class, the result in the data object would be as follows:

```
object(stdClass)#1 (5) {
  ["PageFrontpage"]=>
    array(1) {
      ["SomeData"]=>
      string(13) "This is data."
    }
  }
  ["Header"]=>
  object(stdClass)#2 (0) {
  }
  ["Sidebar"]=>
  object(stdClass)#2 (0) {
  }
  ["Footer"]=>
  object(stdClass)#2 (0) {
  }
}
```

There is also a function called `bind` within the model class. If you want to bind multiple data blocks inside one method, you can use it like so:

```
public function SomeMethod() {
  $data = "This is another piece of data.";

  $this->bind( $data, "SomethingElse" );
}
```

The result would be as follows:

```
object(stdClass)#1 (5) {
  ["PageFrontpage"]=>
    array(1) {
      ["SomethingElse"]=>
      string(13) "This is another piece of data."
    }
  }
  ["Header"]=>
  object(stdClass)#2 (0) {
  }
  ["Sidebar"]=>
  object(stdClass)#2 (0) {
  }
  ["Footer"]=>
  object(stdClass)#2 (0) {
  }
}
```

`bind` can also take a third parameter to create a new primary data block:

```
public function SomeMethod() {
  $data = "This is yet another piece of data.";

  $this->bind( $data, "Method", "PrimaryBlock" );
}
```

The result would be as follows:

```
object(stdClass)#1 (5) {
  ["PageFrontpage"]=>
  object(stdClass)#2 (0) {
  }
  ["PrimaryBlock"] =>
  array(1) {
    ["SomethingElse"]=>
    string(13) "This is yet another piece of data."
  }
  ["Header"]=>
  object(stdClass)#2 (0) {
  }
  ["Sidebar"]=>
  object(stdClass)#2 (0) {
  }
  ["Footer"]=>
  object(stdClass)#2 (0) {
  }
}
```

#### Reserved model names

##### WP

WP is reserved for the essential WordPress data that is accessible in any template all the time. It is stored in the root of the data object with the key `WP` and it contains all the fields that WordPress' native `get_bloginfo()`
would return.

It also contains information about the current user in WP->user and a true/false boolean if the user is logged in in WP->loggedin.

Contents of the `wp_head()` and `wp_footer()` functions are available for use in helpers {@wphead /} and {@wpfooter /} respectively. They should be inserted in the corresponding places in your template file.

```
{@wphead /}
```

## Caching

### Method caching

DustPress has a native support of WordPress transient cache. It works on a method basis, so you can give different methods different TTLs and they get cached automatically.

By default the method caching is disabled. It can be enabled via a filter in your functions.php as follows:

```
add_filter( "dustpress/setting/cache", "__return_true" );
```

However, that setting itself doesn't do anything. You also have to define TTLs for the methods you want to cache. TTL is an abbreviation for Time To Live. It defines the time that a method's cache is alive before it needs to be renewed, i.e. when it's code gets run again.

The TTLs are set in an associative array in a public property of the model called `$ttl` as follows:

```
class Model extends \DustPress\Model {
  public $ttl = [
    "Method" => 60
  ];

  public function Method() {
    // something

    return $data;
  }
}
```

In the example, the data `Method` returns gets cached for 60 seconds. Caching works on [DustPress.js](https://github.com/devgeniem/dustpress-js) requests as well.

### Partial and end-result caching

DustPress also uses WordPress trasient cache for its partials and even the end-result HTML caching. Unlike method caching, these features are enabled by default and you have to disable them if you don't want to use them.

#### Partial caching

Partial caching caches the compiled version of the Dust templates so that they don't have to be compiled from scratch every time the page loads as it is relatively heavy operation in a large scale.

The partials are cached forever, which means that whenever you change them, you probably want to run `wp_cache_flush()` or the corresponding WP CLI command so that they get updated.

You may want to turn the caching off totally while you are in your development environment. That happens, unexpectedly, via a filter as follows:

```
add_filter( "dustpress/cache/partials", "__return_false" );
```

You can also turn the partial caching off only for certain partials:

```
add_filter( "dustpress/cache/partials/partial_name", "__return_false" );
```

#### End-result caching

DustPress also caches the resulting HTML that gets served to the end-user. It generates the cache keys with both the data and the partial used to render the HTML, so that cache updates every time the data changes. It is used only to save the time that DustPHP would use to render the template with the data.

You can turn the end-result caching off in the same way as you would the partial caching:

```
add_filter( "dustpress/cache/rendered", "__return_false" );
```

**Note!** When enabled, [DustPress Debugger](https://github.com/devgeniem/dustpress-debugger) turns both partial and end-result caching off.

## Dust templates

DustPHP templates are 100% compatible with Dust.js templates. See the official [Dust.js website](http://www.dustjs.com/) for documentation or the [LinkedIn Dust Tutorial](https://github.com/linkedin/dustjs/wiki/Dust-Tutorial).

All templates should have a context block with the name of the current model, so that the variables are usable in the template. As for our previous example model, very simplified template could look like this:

```
{>"shared/header" /}

{#PageFrontpage}
  <h1>{WP.name}</h1>
  <h2>{WP.description}</h2>

  <p>{SomeString}</p>

  {SomeHTML|s}

  {">shared/sidebar" /}
{/PageFrontpage}

{>"shared/footer" /}
```

This template includes header.dust, sidebar.dust and footer.dust templates from `partials/shared/` subdirectory. At the end of the `PageFrontpage` block we echo HTML from the `SomeHTML` variable and use the `s` filter to get it _unescaped_.  See the [Dust Tutorial](https://github.com/linkedin/dustjs/wiki/Dust-Tutorial#Sections_and_Context) for more information about sections and contexts.

## DustPress Helpers

Helpers extend the Dust.js templating language with more complex functionality than just data inserting (see: [Context Helpers](http://www.dustjs.com/guides/context-helpers/), [Dust Helpers](http://www.dustjs.com/guides/dust-helpers/)). With DustPress you can use all Dust.js Helpers within your Dust templates. We have also taken it a bit further and included some nice bits for you to use. As mentioned above there are helpers for echoing header and footer data into your templates but here is a complete list of helpers included with DustPress:

### contains

`contains` is a conditional helper. It can be used to determine if an array contains wanted item, so it works like PHP's `in_array`. It can also have an else condition.

Example:
```
{@contains key=haystack value="needle"}
  Found it! :)
{:else}
  Didn't find it. :(
{/contains}
```

### content

`content` helper has two functions. If it is run without parameters, it emulates the use of WordPress' native `the_content()` function and displays the current post's content.

It can also be given a parameter `data` (`{@content data=string /}`). It then behaves like you would run `apply_filters( 'the_content', $string )`.

Example:
```
{@content data=fields.some_content.value /}
```

### menu

`menu` helper does what it name suggests: it creates a menu. It has several parameters that are explained below:

- `menu_id` / `menu_name`: Defines the menu to show. Either is mandatory.
- `depth`: Only show submenus to the level this parameter defines. Defaults to infinite (or PHP_INT_MAX, really).
- `parent`: If parent parameter is defined, the menu features only the subpages of a post with the parameter's value as its ID. Defaults to 0 - all items will be shown.
- `override`: With this parameter you can override what menu item (post ID) will be shown as active (WordPress' default current-menu-item class). Defaults to current post's ID.
- `ul_classes`: Classes that are given to the `ul` element separated with spaces. Per default this is empty.
- `ul_id`: ID that is given to the `ul` element. Per default this is empty.
- `show_submenu`: A boolean value if submenus are shown or not. Defaults to true.
- `menu_partial`: Use another partial instead of the default `menu.dust`. You can use a custom partial by creating your own `menu.dust` inside your theme, and DustPress will use that instead of the one from its core.
- `menuitem_partial`: Use another partial instead of the default `menuitem.dust`. You can use a custom partial by creating your own `menuitem.dust` inside your theme, and DustPress will use that instead of the one from its core.

Example:
```
{@menu menu_name="main-menu" ul_id="main-menu" ul_classes "menu primary-menu" show_submenu=false /}
```

### pagination

`pagination` helper prints out a basic pagination for your template. It takes the data from your model as parameters and calculates the number of pages based on the `posts per page` value. It then prints out an `ul` element containing the page links with the corresponding page as a query parameter. The helper accepts the following parameters:

- `per_page`: The number of items a single page should have.
- `items`: The amount of items in your data set. For example this could be the post count.
- `page_var`: The query parameter for the pagination links. The default is `paged`.
- `hash`: The hash link to be added at the end of the pagination links.

Example:

```
{@pagination per_page=10 items=item_count page_var="paged" hash="posts-section-id" }
```
### permalink

`permalink` helper emulates WordPress' native `get_permalink()` function. It takes one parameter, `id`, that tells it what post's permalink to give. It defaults to current post's id.

Example:
```
{@permalink id=featured_post.ID /}
```

### s

`s` helper emulates WordPress' native `__` function and it is used in internationalization. It takes two parameters: `s` that is the string to be translated and `td` that is the text domain, only `s` is mandatory.

Note that the use of `s` helper does not bring the string available to for example WPML's string scanning function.

Example:
```
{@s s="Home page" td="my-page" /}
```

### sep

`sep` helper is an extension to Dust's native `sep` helper. It behaves the same, but it can also be given two extra parameters: `start` and `end`. They work as the offsets of the function. By default `start` is 0 and `end` is 1.

Example:
```
The participants are {#names}{@last}and {/last}{.}{@sep start=0 end=2}, {/sep}{/names}
```

The result could be:
```
The participants are Bob, Samantha, Michael and Alice.
```

### set and unset

`set` helper can be used to set and alter the data tree in the Dust template. You can create your own variables and assign them values either hardcoded or from the data. You can also perform several mathematic operations on them.

Parameters
- `key`: This parameter is required. It is the name of the variable you want to create or alter.
- `value`: If you want to create a new variable or overwrite a value of another one, give the value you want to give it here.
- `add`: Used to perform mathematical _add_ operation to the value of the variable.
- `subtract`: Used to perform mathematical _subtract_ operation to the value of the variable.
- `multiply`: Used to perform mathematical _multiply_ operation to the value of the variable.
- `divide`: Used to perform mathematical _divide_ operation to the value of the variable.
- `mod`: Used to perform mathematical _modulo_ operation to the value of the variable.

Examples:
```
{@set key="my_variable" value="some_value" /}
{@set key="another_variable" value=path.to.data /}
{@set key="saved_index" value=$idx /}
{@set key="counter" add=1 /}
```

`unset` is used to unset a variable. Usage:
```
{@unset key="my_variable" /}
```

### strtodate

`strtodate` formats the date it is given to a format it is given. It takes three parameters: `value`, `format` and `now`. The function emulates the behaviour of a PHP code: `date( $format, strtotime( $value, $now ) )`.

Example:
```
{@strtodate value=post_date format="d.m.Y H:i:s" /}
```

### title

`Title` works as a proxy for WordPress' native `the_title()` function.

Example:
```
{@title /}
```

### wpfooter

`wpfooter` works as a proxy for WordPress' native `wpfooter()` function.

Example:
```
{@wpfooter /}
```

### wphead

`wphead` works as a proxy for WordPress' native `wphead()` function.

Example:
```
{@wphead /}
```

## Other functionality

### do_not_render

If you do not want the DustPress to render the page automatically but would rather do it yourself, you can call `$this->do_not_render()` anywhere in your model or submodels. In that case DustPress populates the data object, but leaves the
rendering for the developer.

DustPress render function is declared public and is thus usable anywhere. It takes an array of arguments as its parameter. Only mandatory argument is `partial` that contains the name, filename or path to the wanted partial.

With only the partial defined, DustPress passes its global data object to the template. That can be changed by giving it another parameter `data` that would then be passed to the template.

There is also a parameter `type` that defines the format the data would be rendered in. By default it is `html`, but `json` is also a possibility. You can write your own render format functions as well. That feature will be documented later, sorry for that.

The last but not the least of the parameters is `echo` that takes a boolean value. By default it is set to true, so the render function echoes the output straight to browser. If it is false, it is returned as a string. Here is an example usage of the render function:

_in some function_
```
$output = dustpress()->render( [
  "partial"   => 'my_custom_template',
  "data"    => [
      'some_number' => 1,
      'some_string' => 'ABC',
  ],
  "type"    => "html",
  "echo"    => false
]);

echo $output;
```

_my_custom_template.dust_
```
<ul>
    <li>My number: {some_number}</li>
    <li>My string: {some_string}</li>
</ul>
```
_the echoed output_
```
<ul>
    <li>My number: 1</li>
    <li>My string: ABC</li>
<ul>
```

# Additional Classes

## \DustPress\Query

`\DustPress\Query` is a class that contains a few helper functions for common WordPress tasks. The main functionality of this class is customized post querying with the ability to bind basic WordPress metadata to the queried post objects. With a single function call you can get all the meta needed in your Dust template. It also supports customized data fetching for **[Advanced Custom Fields](https://www.advancedcustomfields.com/) (ACF)** field group data in your post objects.

### Querying single posts

#### get_post()

With `\DustPress\Query` you can query single WordPress posts with two different functions. The `get_post()` function accepts the following parameters:
* id: The id of the post.
* args: Arguments in an array.

The argument key `'meta_keys'` accepts meta key values in an array as strings. Passing a string instead of the value `'all'` will fetch all the meta fields in an associative array. The additional argument keys are `'single'` and `'meta_type'` with the same functionality as described in WordPress [documentation](https://codex.wordpress.org/Function_Reference/get_metadata) for `get_metadata()`. Found meta data is appended under the queried post object array with the key `meta`. If no matching post with the passed id is found, `false` is returned.

#### get_acf_post()

This function extends the `get_post()` function with automatic loading of ACF field group data. Fields are loaded with the ACF function [`get_fields`](https://www.advancedcustomfields.com/resources/get_fields/) and are returned into the the post object under the key `fields`. This function accepts the same arguments as the `get_post() function and also the argument key `whole_fields`. With this argument set to `true` this function returns the field group data as seen in the field group edit screen.

This function has a recursive operation. If the argument with the key `recursive` is set to `true`, ACF fields with relational post object data are loaded recursively with full meta and field group data. This recursion also works within the first level of an ACF repeater field.

### Querying multiple posts

#### get_posts()

This function will query multiple posts based on given arguments with the option to get post metadata binded with the post objects. Post objects are queried with the WordPress `get_post` function and the data is extended with metadata. Thus this function accepts the same arguments as the basic `get_post` function. If found, posts are returned as an associative array. If no matching posts are found, `false`is returned. This function accepts arguments in an array with the following keys:
* all the arguments described in the WordPress codex for the `get_posts`function: https://codex.wordpress.org/Function_Reference/get_posts
* meta_keys: Function described in the `get_post()` function. Found meta values are returned automatically for all posts if this argument is set.
* meta_type: Function described in the `get_post()`function

#### get_acf_posts()

This function extends the get_posts function with the ability to load ACF field group data with the post objects. Accepts the same arguments as the `get_posts` function with the addition of the key `whole_fields` which functions similarly as described in the `get_acf_post` function. This function does not have a recursive functionality. ACF fields with relational post object data need to be loaded separately.

# Plugins

## Debugger

DustPress also features a debugger that displays the data loaded by your current model in a pretty JSON viewer.

Get the debugger from [Geniem GitHub](https://github.com/devgeniem/dustpress-debugger) or install it with Composer:
```
composer require devgeniem/dustpress-debugger
```

## DustPress.js

We have also created a handy DustPress.js library for using the DustPress Model methods on the front end.

Get the debugger from [Geniem Github](https://github.com/devgeniem/dustpress-js) or install it with Composer:
```
composer require devgeniem/dustpress-js
```

## Comments helper

If you need to make a commenting feature on your site, you can use our very easy-to-use Comments helper. It is available as a separate plugin.

Get the Comments helper from [Geniem Github](https://github.com/devgeniem/dustpress-comments) or install it with Composer:
```
composer require devgeniem/dustpress-comments
```
