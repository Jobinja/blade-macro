## Blade Macro
This package introduces a new *blade directive* called `@macro` which allows reusable scope-protected *blade* code blocks.
Just like what `@include` does but with zero runtime I/O.

`@include` uses native PHP `include` directive, which causes runtime I/O, **Event if Opcache is fully enabled**.
But sometimes `@include` is used when we want to just **Don't repeat ourselves**
But this abstraction should not cause any performance bottleneck.

### Installation
```bash
composer require jobinja/blade-macro
```
### Usage

Just use the following service provider in your `app.php`:

```php
App\ServiceProvider::class
```
Then you can simply replace your needed old `@include` directives with the new `@macro` one.

### Problem
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
        include './blade_compiled_path/iteration.php';
    ?>
<?php } ?>
```

The above code **includes** the generated file for 5,000,000 times, which causes heavy I/O calls, but the only
reason we have used the `iteration` partial is to create more abstraction and don't repeat our selves.

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

### License
Licensed by MIT, part of the effort for making https://jobinja.ir better.