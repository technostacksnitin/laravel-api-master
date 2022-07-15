# Usage

## Step 1: Install through Composer

```bash 
composer require devdr/laravel-api-master
```

## Step 2: Add new Service Provider in config/app.php inside the providers[] array:

```bash
DevDr\ApiCrudGenerator\DrCrudServiceProvider::class
```

## Step 3: Add this function inside the "app/Exceptions/Handler.php"

```php
use Psy\Util\Json;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

public function _errorMessage($responseCode = 400, $message = 'Bad Request'){
    $body = Json::encode(
        array(
            "success" => false,
            "responseCode" => $responseCode,
            'message' => $message
        )
    );
    echo $body;
    die;
}
```

## Step 4: Add those functions in the "render()"

```php
public function render($request, Exception $exception)
{
    if($exception instanceof NotFoundHttpException){
        $this->_errorMessage(404,'Page Not Found');
    }

    if ($exception instanceof MethodNotAllowedHttpException) {
        $this->_errorMessage(405,'Method is not allowed for the requested route');
    }

    return parent::render($request, $exception);
}
```
## Step 5: Add this line into your "app/Http/Kernel.php" $routeMiddleware[]

```php
'api.auth' => \DevDr\ApiCrudGenerator\Middleware\CheckAuth::class,
```
Now you can use this 'api.auth' middleware anywhere

#### You can pass the "AUTH-TOKEN" in api headers for the check authentication

## Step 6: User "crud:api-generator" for the crud creation
```php
php artisan crud:api-generator User
```

## Step 7: After using middleware that in that function you can use the user object by this

```php
$user = $request->get('users');
```

## Step 8: Add this function in the Users Model

```php
public static function findIdentityByAccessToken($token, $type = null)
{
    return static::where(['auth_token' => $token])->first();
}
```
#### OR

without middleware you can use bellow function into the direct controller api action
```php
$user = $this->_checkAuth();
```

## Contributing
We're not suggesting you to use this package. Use it as your own RISK.

