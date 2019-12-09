# DS Grid
Model visualization for laravel

## Installation

    composer require darthshell/grid
    php artisan vendor:publish --provider="DarthShelL\Grid\GridServiceProvider"


## Usage

Please, make sure u have "scripts" and "styles" sections in your layout.
Layout example:
    
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        ...
        <!-- Styles -->
        ...
        @yield('styles')
    
        <!-- Scripts -->
        ...
        @yield('scripts')
    </head>
    ...

Then in the view you want to contain a grid add a line:

    {!! $provider->renderGrid() !!}
    
And also don't forget about controller action:

    use App\Http\Controllers\Controller;
    use DarthShelL\Grid\DataProvider;
    
    class MySuperController extends Controller
    {
        public function index()
        {
    
            $provider = new DataProvider(new ModelIWantToShow());
            $provider->processUpdate();
    
            return view('index', compact('provider'));
        }
    }

That's all!


## Short doc

### setting rows number per page

`$provider->perPage = 15;`

### hiding column

`$provider->hideColumn('column_name');`

`$provider->hideColumn('column_name','column2_name',...);`

### adding filter

#### integer filter
 it also supports operators [ >, <, =, >=, <=, >< ]

`$provider->addFilter('id', $provider::INTEGER);`

#### string filter
 it also supports operator [ % ] with sintax equal to SQL LIKE

`$provider->addFilter('name', $provider::STRING);`

### adding column format

    $provider->addFormat('type', function($row) {
        $types = [
            0 => 'span',
            1 => 'link'
        ];
        return $types[$row->type];
    });
