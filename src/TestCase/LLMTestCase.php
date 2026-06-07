<?php

namespace LLMesh\Eval\TestCase;

use PHPUnit\Framework\TestCase;
use LLMesh\Eval\Assertions\LLMAssertions;

abstract class LLMTestCase extends TestCase
{
    use LLMAssertions;
}
