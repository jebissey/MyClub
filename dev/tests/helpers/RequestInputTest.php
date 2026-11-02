<?php

declare(strict_types=1);

namespace tests\helpers;

use PHPUnit\Framework\TestCase;
use flight\net\Request;
use app\helpers\RequestInput;

final class RequestInputTest extends TestCase
{
    /**
     * Builds a RequestInput on top of a real Flight request.
     *
     * The collections are filled after construction because Request::init() reads the
     * superglobals itself and would otherwise ignore anything given beforehand.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $form
     * @param array<string, mixed> $cookies
     */
    private function makeInput(array $query = [], array $form = [], array $cookies = []): RequestInput
    {
        $request = new Request();
        $request->query->setData($query);
        $request->data->setData($form);
        $request->cookies->setData($cookies);

        return new RequestInput($request);
    }

    // -------------------------------------------------------------------------
    // query / form
    // -------------------------------------------------------------------------

    public function testQueryReturnsQueryStringParameters(): void
    {
        $input = $this->makeInput(query: ['a' => '1', 'b' => 'two']);

        $this->assertSame(['a' => '1', 'b' => 'two'], $input->query());
    }

    public function testQueryDoesNotReturnFormData(): void
    {
        $input = $this->makeInput(query: ['a' => '1'], form: ['b' => '2']);

        $this->assertArrayNotHasKey('b', $input->query());
    }

    public function testFormReturnsFormData(): void
    {
        $input = $this->makeInput(form: ['name' => 'Alice']);

        $this->assertSame(['name' => 'Alice'], $input->form());
    }

    public function testFormDoesNotReturnQueryParameters(): void
    {
        $input = $this->makeInput(query: ['a' => '1'], form: ['b' => '2']);

        $this->assertArrayNotHasKey('a', $input->form());
    }

    // -------------------------------------------------------------------------
    // cookie
    // -------------------------------------------------------------------------

    public function testCookieReturnsValue(): void
    {
        $input = $this->makeInput(cookies: ['user_language' => 'en_US']);

        $this->assertSame('en_US', $input->cookie('user_language'));
    }

    public function testCookieReturnsNullWhenMissing(): void
    {
        $input = $this->makeInput();

        $this->assertNull($input->cookie('user_language'));
    }

    public function testCookieReturnsNullWhenValueIsNotAString(): void
    {
        $input = $this->makeInput(cookies: ['user_language' => ['x']]);

        $this->assertNull($input->cookie('user_language'));
    }

    public function testCookieKeepsEmptyString(): void
    {
        $input = $this->makeInput(cookies: ['rememberMe' => '']);

        $this->assertSame('', $input->cookie('rememberMe'));
    }

    // -------------------------------------------------------------------------
    // queryBool
    // -------------------------------------------------------------------------

    public function testQueryBoolAcceptsTrueValues(): void
    {
        foreach (['1', 'on', 'true'] as $value) {
            $this->assertTrue($this->makeInput(query: ['flag' => $value])->queryBool('flag'), $value);
        }
    }

    public function testQueryBoolAcceptsFalseValues(): void
    {
        foreach (['0', 'off', 'false'] as $value) {
            $this->assertFalse($this->makeInput(query: ['flag' => $value])->queryBool('flag'), $value);
        }
    }

    public function testQueryBoolReturnsDefaultWhenMissing(): void
    {
        $input = $this->makeInput();

        $this->assertNull($input->queryBool('flag'));
        $this->assertTrue($input->queryBool('flag', true));
    }

    public function testQueryBoolReturnsDefaultWhenInvalid(): void
    {
        $input = $this->makeInput(query: ['flag' => 'maybe']);

        $this->assertNull($input->queryBool('flag'));
        $this->assertFalse($input->queryBool('flag', false));
    }

    // -------------------------------------------------------------------------
    // queryInt
    // -------------------------------------------------------------------------

    public function testQueryIntReturnsInteger(): void
    {
        $input = $this->makeInput(query: ['page' => '12']);

        $this->assertSame(12, $input->queryInt('page'));
    }

    public function testQueryIntReturnsDefaultWhenMissing(): void
    {
        $input = $this->makeInput();

        $this->assertNull($input->queryInt('page'));
        $this->assertSame(1, $input->queryInt('page', 1));
    }

    public function testQueryIntReturnsDefaultWhenNotNumeric(): void
    {
        $input = $this->makeInput(query: ['page' => 'abc']);

        $this->assertSame(1, $input->queryInt('page', 1));
    }

    public function testQueryIntReturnsDefaultWhenValueIsAnArray(): void
    {
        $input = $this->makeInput(query: ['page' => ['1', '2']]);

        $this->assertSame(1, $input->queryInt('page', 1));
    }

    // -------------------------------------------------------------------------
    // queryString
    // -------------------------------------------------------------------------

    public function testQueryStringReturnsValue(): void
    {
        $input = $this->makeInput(query: ['q' => 'hello']);

        $this->assertSame('hello', $input->queryString('q'));
    }

    public function testQueryStringReturnsDefaultWhenMissingOrEmpty(): void
    {
        $this->assertNull($this->makeInput()->queryString('q'));
        $this->assertSame('fallback', $this->makeInput(query: ['q' => ''])->queryString('q', default: 'fallback'));
    }

    public function testQueryStringAcceptsOnlyAllowedValues(): void
    {
        $allowed = ['day', 'week'];

        $this->assertSame('week', $this->makeInput(query: ['period' => 'week'])->queryString('period', $allowed));
        $this->assertNull($this->makeInput(query: ['period' => 'year'])->queryString('period', $allowed));
        $this->assertSame('day', $this->makeInput(query: ['period' => 'year'])->queryString('period', $allowed, 'day'));
    }

    public function testQueryStringStripsTags(): void
    {
        $input = $this->makeInput(query: ['q' => '<b>bold</b>']);

        $this->assertSame('bold', $input->queryString('q'));
    }

    // -------------------------------------------------------------------------
    // formString
    // -------------------------------------------------------------------------

    public function testFormStringReturnsValue(): void
    {
        $input = $this->makeInput(form: ['title' => 'Hello']);

        $this->assertSame('Hello', $input->formString('title'));
    }

    public function testFormStringReturnsDefaultWhenMissing(): void
    {
        $input = $this->makeInput();

        $this->assertSame('', $input->formString('title'));
        $this->assertSame('none', $input->formString('title', 'none'));
    }

    public function testFormStringReturnsDefaultWhenValueIsAnArray(): void
    {
        $input = $this->makeInput(form: ['title' => ['injected']]);

        $this->assertSame('none', $input->formString('title', 'none'));
    }

    // -------------------------------------------------------------------------
    // buildUrl
    // -------------------------------------------------------------------------

    public function testBuildUrlMergesCurrentQueryWithNewParameters(): void
    {
        $input = $this->makeInput(query: ['period' => 'day', 'date' => '2026-11-01']);

        $this->assertSame('?period=week&date=2026-11-01', $input->buildUrl(['period' => 'week']));
    }

    public function testBuildUrlAddsNewParameters(): void
    {
        $input = $this->makeInput(query: ['period' => 'day']);

        $this->assertSame('?period=day&date=2026-11-02', $input->buildUrl(['date' => '2026-11-02']));
    }

    public function testBuildUrlDropsParametersSetToNull(): void
    {
        $input = $this->makeInput(query: ['period' => 'day', 'date' => '2026-11-01']);

        $this->assertSame('?period=day', $input->buildUrl(['date' => null]));
    }

    public function testBuildUrlWithoutAnyParameterReturnsBareQuestionMark(): void
    {
        $this->assertSame('?', $this->makeInput()->buildUrl([]));
    }
}