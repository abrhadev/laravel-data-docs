<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\UrlProcessor;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new UrlProcessor();
});

it('sets the uri format and states the protocols', function (Url $attribute, string $sentence, ?string $scheme) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process($attribute, $context);

    expect($context->format)->toBe('uri')
        ->and($context->descriptions)->toBe([$sentence])
        ->and($context->uriScheme)->toBe($scheme)
        ->and($context->example)->toBeNull();
})->with([
    'no protocol'          => fn() => [new Url(), 'Must be a valid URL.', null],
    'https'                => fn() => [new Url('https'), 'Must be a valid URL using the <code>https</code> protocol.', null],
    'another protocol'     => fn() => [new Url('ftp'), 'Must be a valid URL using the <code>ftp</code> protocol.', 'ftp'],
    'https among others'   => fn() => [new Url('ftp', 'HTTPS'), 'Must be a valid URL using one of the protocols: <code>ftp</code>, <code>HTTPS</code>.', null],
    'first plain protocol' => fn() => [new Url('coap+tcp', 'sftp'), 'Must be a valid URL using one of the protocols: <code>coap+tcp</code>, <code>sftp</code>.', 'sftp'],
    'external reference'   => fn() => [new Url(new RouteParameterReference('scheme')), 'Must be a valid URL.', null],
    'a repeated protocol'  => fn() => [new Url('ftp', 'ftp'), 'Must be a valid URL using the <code>ftp</code> protocol.', 'ftp'],
]);

it('records no scheme for a field that is not a string', function () {
    $context = conditionContext();
    $context->type = 'string[]';

    $this->processor->process(new Url('ftp'), $context);

    expect($context->uriScheme)->toBeNull();
});
