<?php

namespace Amp\Pipeline\Internal;

use Amp\Cancellation;
use Amp\Pipeline\Operator;
use Amp\Pipeline\Pipeline;

/**
 * Wraps an EmitSource instance that has public methods to emit, complete, and fail into an object that only allows
 * access to the public API methods and automatically calls EmitSource::destroy() when the object is destroyed.
 *
 * @internal
 *
 * @template TValue
 * @template-implements Pipeline<TValue>
 * @template-implements \IteratorAggregate<int, TValue>
 */
final class AutoDisposingPipeline implements Pipeline, \IteratorAggregate
{
    /** @var EmitSource<TValue> */
    private EmitSource $source;

    public function __construct(EmitSource $source)
    {
        $this->source = $source;
    }

    public function __destruct()
    {
        $this->source->destroy();
    }

    /**
     * @inheritDoc
     */
    public function continue(?Cancellation $cancellation = null): mixed
    {
        return $this->source->continue($cancellation);
    }

    /**
     * @inheritDoc
     */
    public function dispose(): void
    {
        $this->source->dispose();
    }

    /**
     * @template TResult
     *
     * @param Operator ...$operators
     *
     * @return Pipeline<TResult>
     */
    public function pipe(Operator ...$operators): Pipeline
    {
        $pipeline = $this;
        foreach ($operators as $operator) {
            $pipeline = $operator->pipe($pipeline);
        }

        /** @var Pipeline<TResult> $pipeline */
        return $pipeline;
    }

    /**
     * @inheritDoc
     */
    public function isComplete(): bool
    {
        return $this->source->isConsumed();
    }

    /**
     * @inheritDoc
     */
    public function isDisposed(): bool
    {
        return $this->source->isDisposed();
    }

    /**
     * @inheritDoc
     *
     * @psalm-return \Traversable<int, TValue>
     */
    public function getIterator(): \Traversable
    {
        while (null !== $value = $this->source->continue()) {
            yield $value;
        }
    }
}
