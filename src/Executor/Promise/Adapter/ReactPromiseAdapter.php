<?php declare(strict_types=1);

namespace GraphQL\Executor\Promise\Adapter;

use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;

use function React\Promise\all;

use React\Promise\Promise as ReactPromise;
use React\Promise\PromiseInterface as ReactPromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

class ReactPromiseAdapter implements PromiseAdapter
{
    public function isThenable($value): bool
    {
        return $value instanceof ReactPromiseInterface;
    }

    public function convertThenable($thenable): Promise
    {
        return new Promise($thenable, $this);
    }

    public function then(Promise $promise, ?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        $adoptedPromise = $promise->adoptedPromise;
        assert($adoptedPromise instanceof ReactPromiseInterface);

        return new Promise($adoptedPromise->then($onFulfilled, $onRejected), $this);
    }

    public function create(callable $resolver): Promise
    {
        $promise = new ReactPromise($resolver);

        return new Promise($promise, $this);
    }

    public function createFulfilled($value = null): Promise
    {
        $promise = resolve($value);

        return new Promise($promise, $this);
    }

    public function createRejected(\Throwable $reason): Promise
    {
        $promise = reject($reason);

        return new Promise($promise, $this);
    }

    public function all(iterable $promisesOrValues): Promise
    {
        assert(
            is_array($promisesOrValues),
            'ReactPromiseAdapter::all(): Argument #1 ($promisesOrValues) must be of type array'
        );

        foreach ($promisesOrValues as &$promiseOrValue) {
            if ($promiseOrValue instanceof Promise) {
                $promiseOrValue = $promiseOrValue->adoptedPromise;
            }
        }

        $promise = all($promisesOrValues)->then(static fn ($values): array => array_map(
            static fn ($key) => $values[$key],
            array_keys($promisesOrValues),
        ));

        return new Promise($promise, $this);
    }
}
