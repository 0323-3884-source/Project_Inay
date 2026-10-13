<div class="f1kd-family-photos">
    @php($photos = [(object)['name'=>$beneficiary->name, 'photo_path'=>$beneficiary->photo_path, 'label'=>$beneficiary->classification === 'child' ? 'Child' : 'Mother']])
    @foreach($photos as $person)
        <figure>
            @if($person->photo_path)
                <img src="{{ asset('storage/'.$person->photo_path) }}" alt="Photo of {{ $person->name }}" loading="lazy">
            @else
                <div class="f1kd-photo-placeholder" role="img" aria-label="No photo for {{ $person->name }}">No photo</div>
            @endif
            <figcaption><strong>{{ $person->name }}</strong><br>{{ $person->label }}</figcaption>
        </figure>
    @endforeach
</div>
<style>
.f1kd-family-photos { display:flex; flex-wrap:wrap; gap:20px; margin-bottom:24px; }
.f1kd-family-photos figure { margin:0; width:150px; }
.f1kd-family-photos img, .f1kd-photo-placeholder { width:112px; height:112px; border-radius:16px; object-fit:cover; border:1px solid #d9e2ef; }
.f1kd-photo-placeholder { display:grid; place-items:center; background:#f8edf3; color:#71566a; }
.f1kd-family-photos figcaption { font-size:13px; line-height:1.5; margin-top:8px; overflow-wrap:anywhere; }
</style>
