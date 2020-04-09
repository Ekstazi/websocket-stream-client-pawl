# websocket-stream-client-pawl
`ekstazi/websocket-stream-client-pawl` is `ekstazi/websocket-stream-client` implementation based on `ratchet/pawl`
# Installation
This package can be installed as a Composer dependency.

`composer require ekstazi/websocket-stream-client-pawl`
# Requirements
PHP 7.2+
# Usage
## With container
If you have container then add this to your `container.php`
```php
use \ekstazi\websocket\client\pawl\ConnectorFactory;
use \ekstazi\websocket\client\ConnectionFactory;

// ....

return [
    ConnectionFactory::class => new ConnectorFactory(),
];
```
Then in your code:
```php
use \Psr\Container\ContainerInterface;
use \ekstazi\websocket\client\ConnectionFactory;
use \Psr\Http\Message\RequestInterface;
use \ekstazi\websocket\client\Connection;

/** @var ContainerInterface $container */
/** @var ConnectionFactory $connector */
/** @var RequestInterface $request */

$connector = $container->get(ConnectionFactory::class);

/** @var Connection $stream */
$stream = yield $connector->connect($request, Connection::MODE_BINARY);

```

### Without container
You can use functions to do the same:
```php
use \ekstazi\websocket\client\ConnectionFactory;
use \Psr\Http\Message\RequestInterface;
use \ekstazi\websocket\client\Connection;

use function \ekstazi\websocket\client\connect;

/** @var RequestInterface $request */
/** @var Connection $stream */
$stream = yield connect($request, Connection::MODE_BINARY);
```
or
```php
use \ekstazi\websocket\client\ConnectionFactory;
use \Psr\Http\Message\RequestInterface;
use \ekstazi\websocket\client\Connection;

use function \ekstazi\websocket\client\connector;

/** @var RequestInterface $request */
/** @var ConnectionFactory $connector */
$connector = connector();

/** @var Connection $stream */
$stream = yield $connector->connect($request, Connection::MODE_BINARY);
```
