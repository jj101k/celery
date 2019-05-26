# Changes

## 1.1.0

- Incompatible change: responses now accept an optional header block not an
  iterator

## 1.0.7

- Parsed query string support
- http/https scheme detection
- Bug: Only PATH_INFO was used to detect the path, leaving environments which
  don't use redirect-like behaviour unable to parse the URL. This now uses
  REQUEST_URI where PATH_INFO is not present, but decodes it.

## 1.0.6

- Bug: Query strings were appearing twice in back-end URLs

## 1.0.5

- Bug: Response objects with iterators were not iterated before some `with*`
  methods

## 1.0.4

- Bug: PUT/POST bodies were missed entirely
- Bug: HTTP response code and headers were missing
- Responses now have an initial content type of text/html

## 1.0.3

- Testfix: Correct spurious warning about end-of-headers where the
header string includes a trailing empty line `"\r\n\r\n"`

## 1.0.2

- Response/Body support for streaming an HTTP envelope as provided by an iterator

## 1.0.1

- Split out the request handling so that it can be used directly
- Bug: default not-found handler should not expect a methods argument

## 1.0.0

- Initial version