SCSS Watcher
============

The SCSS Watcher is a PHP class that recursively monitors a directory for changed `.scss` files and generates `.css` versions.
It requires a `sass` binary to be installed.

The CSS files are generated in `css` directories at the same level as the corresponding `scss` directory, e.g.:

    /myproject/module1/scss/module1.scss
    /myproject/module2/scss/module2.scss
    =>
    /myproject/module1/css/module1.css
    /myproject/module2/css/module2.css

SCSS files with a leading `_` in the filename are treated as SCSS configuration files (e.g. for mixins).
A change to a system file triggers a re-build of all CSS files.

### Installation

Add the repository reference to your `composer.json`:

    "require-dev": {
        "bnowack/scss-watcher": "1.0.0"
    }
    

### Usage

Ideally, the SCSS Watcher is run from the command line. A script is provided:

    php scripts/watch.php --path=/path/to/entry/directory

or (from your repository root):

    php vendor/bnowack/scss-watcher/scripts/watch.php --path=src --bin=/usr/bin/sass

The "bin" parameter is optional. It can be used when auto-detection cannot find the sass binary. 
On Windows machines the command usually looks like this:

    php vendor/bnowack/scss-watcher/scripts/watch.php --path=src --bin=C:\Ruby\bin\sass

### License

[The MIT License (MIT)](http://opensource.org/licenses/MIT)
