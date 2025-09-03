# Notes on Updating

## `v4.0.0`

`Connection->saveAll()` and `Connection->deleteAll()` no longer accept arrays, please update your code to pass variadic parameters.

```php

// Before v4.0.0
$connection->saveAll([$model1, $model2]);

// After v4.0.0
$connection->saveAll($model1, $model2);
```
