## Synopsis

SmileScreen is a simple MVC framework written by myself DrNotThatEvil.  

## Router Example

Here is example of the router included.

``` php 
<?php 

  use SmileScreen\Routing\Router as Router;

  $router = Router::getInstance();

  // example of a simple route
  $router->get('/hello', function() { 
      echo "Hello back at ya";
  });

  // router pregmatch example
  $router->get('/level/(\d+)', function($level) {
      if($level > 9000) {
          echo 'IT\'S OVER NINETHOUSAND!!!!!!';
      } else {
          echo 'Your level aint no match for me!';
      }
  });

  // And of course class methods are also possibles 
  $router->get('/callthatclass', 'SomePackage\Controllers\AwesomeClass@hello');
```

## Motivation

For school we needed to create a project with some rules one of witch no external framework  
also no composer packages where to be used (we where allowed to use composers autoloader).  

So I started this MVC framework but I just loved working on the nitty grity framework stuff so mutch  
that I decided to keep this framework and put it in it's own git repository cause I felt proud.  

## Installation

I recommend using composer once we hit version 1.0.0
But you could use clone this project and add it to your composer autoloader

for now you can install the codebase and just add it to your composer.json file
to start using it.

```
composer install drnotthatevil/smilescreen
```

## Wanna contribute?

Feel free to fork! Then make a pull request with your improvements I'm always open to suggestions to!

## License

The MIT License (MIT)

Copyright (c) 2015 Willmar Knikker

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
