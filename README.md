## Blade Macro
This package introduces a new *blade directive* called `@macro` which allows reusable scope-protected *blade* code blocks.
Just like what `@include` does but with zero runtime I/O.

`@include` uses native PHP `include` directive, which causes runtime I/O, **Event if Opcache is fully enabled**.
But sometimes `@include` is used when we want to just **Don't repeat ourselves**
But this abstraction should not cause any performance bottleneck.

### Installation (Laravel 5.1 and 5.3)
```bash
composer require jobinja/blade-macro
```
### Usage

Just use the following service provider in your `app.php`:

```php
[
    \JobinjaTeam\BladeMacro\JobinjaBladeMacroServiceProvider::class,
    //...
]
```
Then you can simply replace your needed old `@include` directives with the new `@macro` one:

```php
@include('some_partial', ['some_var' => 'some_value')

// Should be replaced with:
@macro('some_partial', ['some_var' => 'some_value'])
```

### Configuration

By default the package re-compiles blade views on each request in **development** environment, if you want to disable this feature run:
```bash
php artisan vendor:publish --provider=JobinjaTeam\BladeMacro\JobinjaBladeMacroServiceProvider
```
and config the package based on your needs.

### Problem
Please see [#16583](https://github.com/laravel/framework/pull/16583) or simply read the following:

Consider the following loop:

```php
@for($i=1; $i < 500000; $i++)
    @include('iteration_presenter', ['iteration' => $i])
@endfor
```

The above code will be replaced by something like the following:

```php
<?php for($i=1; $i < 5000000; $i++){ ?>
    <?php
        // Just for example in real world laravel wraps this
        // around a method to satisfy scope protected data.
        include './blade_compiled_path/iteration_presenter.php';
    ?>
<?php } ?>
```

The above code **includes** the **iteration_presenter.blade.php** file for 5,000,000 times, which causes heavy I/O calls, but the only
reason we have used the `iteration_presenter` partial is to create more abstraction and don't repeat ourselves.

### Solution
Instead of using native `include` directive we have created a new `@macro` directive which simply copy/pastes the
partial content in compile time, and simply there is no I/O then:

```php
@for($i=1; $i < 500000; $i++)
    @macro('iteration_presenter', ['iteration' => $i])
@endfor
```

The above `@macro` directive will be translated to the following:
```php
<?php for($i=1; $i < 5000000; $i++){ ?>
    <?php (function ($scopeData) { extract($scopeData); ?>

    <?php echo e($iteration);?>

    <?php })($someHowComputedScope) ;?>
<?php } ?>
```

### Running tests
```bash
composer test
```

### License
Licensed under MIT, part of the effort for making [Jobinja.ir](https://jobinja.ir) better.