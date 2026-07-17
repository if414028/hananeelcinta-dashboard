{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach([route('home'),route('about'),route('announcements.index'),route('pastor-messages.index'),route('family-altars.index'),route('prayer-request.create'),route('contact'),route('privacy'),route('terms')] as $url)<url><loc>{{ $url }}</loc></url>@endforeach
@foreach($announcements as $item)<url><loc>{{ route('announcements.show',$item) }}</loc><lastmod>{{ $item->updated_at->toAtomString() }}</lastmod></url>@endforeach
@foreach($pastorMessages as $item)<url><loc>{{ route('pastor-messages.show',$item) }}</loc><lastmod>{{ $item->updated_at->toAtomString() }}</lastmod></url>@endforeach
</urlset>
