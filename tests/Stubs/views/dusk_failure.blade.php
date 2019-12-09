@forelse ($files as $file)
  <img src="{{ $message->embed($file->getPathname()) }}">
@empty
  No images found
@endforelse