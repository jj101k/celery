# Changes

## 1.3.0

- Add an explicit mechanism to split a stream into writable and readable
  (`writableCopy()`)
- BUG: `withUploadedFiles()` was expecting `$_FILES` format not PSR-7 format
- BUG: file uploads with structured names, eg. `foo[bar][]` were not added
  correctly
- BUG: `getParsedBody()` never did anything
- BUG: `withJSON()` never set the content type
- BUG: Bodies did not report that `php://input` is not seekable
- Switched to `php://temp` for ad-hoc bodies for better large-file support
- Stream objects (bodies) are now generally readable or writable but not both
- Request body is now directly attached to `php://input` so that it can be
  streamed where needed.
- `\Celery\StreamFile` is now implemented through `\Celery\Body`
- Various warning reductions
- Various testing improvements

## 1.2.0

- Minor fix to streaming bodies: always emitted the first two chunks on the
  first call
- Implemented streaming at the top level, because that wasn't a thing yet.

## 1.1.4

- Re-add support for "streaming read" bodies

## 1.1.3

- BUG: Empty (eg. HEAD) responses were crashing on fread()

## 1.1.2

- Seperately track position in the body to support multiple places using the
  same filehandle

## 1.1.1

- Support an explicitly set size on message bodies
- Make body objects rewritable

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