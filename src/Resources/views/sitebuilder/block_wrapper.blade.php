<div sitebuilder-block increment="{{ $increment }}" type="{{ $block->getPrefix() }}" {{ $increment %2===0 ? 'even' : 'odd' }}>
    {!! $content !!}
</div>