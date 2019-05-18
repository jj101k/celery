# Changes

## 1.0.0

- Initial version

## 1.0.1

- Split out the request handling so that it can be used directly
- Bug: default not-found handler should not expect a methods argument

## 1.0.2

- Response/Body support for streaming an HTTP envelope as provided by an iterator

## 1.0.3

- Testfix: Correct spurious warning about end-of-headers where the
header string includes a trailing empty line `"\r\n\r\n"`
