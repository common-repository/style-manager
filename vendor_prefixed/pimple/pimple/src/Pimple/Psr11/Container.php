<?php

/*
 * This file is part of Pimple.
 *
 * Copyright (c) 2009-2017 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Pixelgrade\StyleManager\Vendor\Pimple\Psr11;

use Pixelgrade\StyleManager\Vendor\Pimple\Container as PimpleContainer;
use Pixelgrade\StyleManager\Vendor\Psr\Container\ContainerInterface;
/**
 * PSR-11 compliant wrapper.
 *
 * @author Pascal Luna <skalpa@zetareticuli.org>
 */
final class Container implements \Pixelgrade\StyleManager\Vendor\Psr\Container\ContainerInterface
{
    private $pimple;
    public function __construct(\Pixelgrade\StyleManager\Vendor\Pimple\Container $pimple)
    {
        $this->pimple = $pimple;
    }
    /**
     * @param string $id
     */
    public function get($id)
    {
        return $this->pimple[$id];
    }
    /**
     * @param string $id
     */
    public function has($id) : bool
    {
        return isset($this->pimple[$id]);
    }
}
