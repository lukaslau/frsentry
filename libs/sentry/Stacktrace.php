<?php
/*
 * Copyright (c) 2026 Frento IT <info@frentoit.com>
 *
 * NOTICE OF LICENSE
 *
 * This file is licensed under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the license agreement.
 *
 * You must not modify, adapt or create derivative works of this source code.
 *
 * @author    Frento IT <info@frentoit.com>
 * @copyright Since 2024 Frento IT
 * @license   Commercial license
 */

declare(strict_types=1);

namespace FrSentry\Sentry;

/**
 * This class contains all the information about an error stacktrace.
 *
 * @author Stefano Arlandini <sarlandini@alice.it>
 */
final class Stacktrace
{
    /**
     * @var Frame[] The frames that compose the stacktrace
     */
    private $frames = [];

    /**
     * Constructor.
     *
     * @param Frame[] $frames A non-empty list of stack frames. The list must be
     *                        ordered from caller to callee. The last frame is the
     *                        one creating the exception
     */
    public function __construct(array $frames)
    {
        if (empty($frames)) {
            throw new \InvalidArgumentException('Expected a non empty list of frames.');
        }
        foreach ($frames as $frame) {
            if (!$frame instanceof Frame) {
                throw new \UnexpectedValueException(\sprintf('Expected an instance of the "%s" class. Got: "%s".', Frame::class, get_debug_type($frame)));
            }
        }
        $this->frames = $frames;
    }

    /**
     * Gets the stacktrace frames.
     *
     * @return Frame[]
     */
    public function getFrames(): array
    {
        return $this->frames;
    }

    /**
     * Gets the frame at the given index.
     *
     * @param int $index The index from which the frame should be get
     *
     * @throws \OutOfBoundsException
     */
    public function getFrame(int $index): Frame
    {
        if ($index < 0 || $index >= \count($this->frames)) {
            throw new \OutOfBoundsException();
        }

        return $this->frames[$index];
    }

    /**
     * Adds a new frame to the stacktrace.
     *
     * @param Frame $frame The frame
     */
    public function addFrame(Frame $frame): self
    {
        array_unshift($this->frames, $frame);

        return $this;
    }

    /**
     * Removes the frame at the given index from the stacktrace.
     *
     * @param int $index The index of the frame
     *
     * @throws \OutOfBoundsException If the index is out of range
     */
    public function removeFrame(int $index): self
    {
        if (!isset($this->frames[$index])) {
            throw new \OutOfBoundsException(\sprintf('Cannot remove the frame at index %d.', $index));
        }
        if (\count($this->frames) === 1) {
            throw new \RuntimeException('Cannot remove all frames from the stacktrace.');
        }
        array_splice($this->frames, $index, 1);

        return $this;
    }
}
