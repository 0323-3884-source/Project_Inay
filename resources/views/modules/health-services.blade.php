@extends('layouts.app')

@section('title', 'Health Services - Project INAY')
@section('portal_title', 'Health Services')

@php
    $iconHospital = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v4"/><path d="M14 9h-4"/><path d="M18 21V5a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/><path d="M18 11h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h2"/></svg>';
    $iconMapPin = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
    $iconLocate = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v3"/><path d="M12 19v3"/><path d="M2 12h3"/><path d="M19 12h3"/><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/></svg>';
    $iconInfo = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
    $iconFilter = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3H2l8 9.5V20l4 2v-9.5L22 3Z"/></svg>';
    $iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
    $iconShield = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>';
    $iconNavigation = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 19-9-9 19-2-8-8-2Z"/></svg>';
@endphp

@section('content')
    <style>
        .health-services{--pink:#ec008c;--navy:#071127;--muted:#52627d;--line:#dbe5f1;--soft:#f8fafc;--green:#00856a;--blue:#2563eb;color:var(--navy);font-weight:400}.health-services *{box-sizing:border-box;letter-spacing:0}.health-services svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.hs-hero{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,410px);gap:20px;align-items:stretch;padding:22px;border:1px solid var(--line);border-radius:10px;background:#fff;box-shadow:0 2px 8px rgba(15,23,42,.08);margin-bottom:18px}.hs-kicker{display:inline-flex;align-items:center;gap:8px;margin:0 0 12px;color:var(--pink);font-size:12px;font-weight:600;text-transform:uppercase}.hs-title{margin:0;font-size:30px;line-height:1.12;font-weight:700;color:#020817}.hs-copy{margin:10px 0 0;max-width:760px;color:var(--muted);font-size:15px;line-height:1.6;font-weight:400}.hs-note-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:16px}.hs-mini-note{padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;color:#40516f;font-size:13px;line-height:1.45;font-weight:400}.hs-location-box{padding:18px;border:1px solid #fbcfe8;border-radius:10px;background:#fdf2f8}.hs-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;border:1px solid var(--pink);border-radius:8px;background:var(--pink);color:#fff;padding:0 16px;font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;transition:background .18s ease,transform .18s ease}.hs-button:hover{background:#d6007d;transform:translateY(-1px);text-decoration:none}.hs-button:disabled{background:#f9a8d4;border-color:#f9a8d4;cursor:not-allowed;transform:none}.hs-button.is-outline{background:#fff;color:var(--pink);border-color:#f9a8d4}.hs-status{margin:12px 0 0;color:#31415f;font-size:14px;line-height:1.5;font-weight:500}.hs-status.is-error{color:#be123c}.hs-location-note{margin-top:12px;padding:10px 12px;border:1px solid #fbcfe8;border-radius:8px;background:#fff;color:#52627d;font-size:12px;line-height:1.45}.hs-nearest{display:none;margin-top:12px;padding:10px 12px;border:1px solid #fbcfe8;border-radius:8px;background:#fff;color:var(--pink);font-size:12px;font-weight:600;text-transform:uppercase;line-height:1.4}.hs-nearest.is-visible{display:block}.hs-info{padding:18px;border:1px solid #bfdbfe;border-radius:10px;background:#eff6ff;margin-bottom:18px}.hs-info h2{display:flex;align-items:center;gap:8px;margin:0 0 10px;color:#12336d;font-size:17px;font-weight:600}.hs-info ul{margin:0;padding-left:20px;color:#31415f;font-size:14px;line-height:1.7}.hs-filter-panel{padding:16px;border:1px solid var(--line);border-radius:10px;background:#fff;box-shadow:0 2px 8px rgba(15,23,42,.06);margin-bottom:18px}.hs-filter-head{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:12px}.hs-filter-title{display:flex;align-items:center;gap:8px;margin:0;color:#52627d;font-size:12px;text-transform:uppercase;font-weight:600}.hs-result-count{margin:0;color:#64748b;font-size:13px}.hs-filters{display:flex;flex-wrap:wrap;gap:8px}.hs-chip{border:1px solid #dbe5f1;background:#fff;color:#40516f;border-radius:999px;min-height:36px;padding:0 13px;font-size:12px;font-weight:600;cursor:pointer;transition:background .18s ease,border-color .18s ease,color .18s ease}.hs-chip:hover,.hs-chip.is-active{border-color:var(--pink);background:#fdf2f8;color:var(--pink)}.hs-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start}.hs-list{display:grid;gap:14px}.hs-card{border:1px solid var(--line);border-radius:10px;background:#fff;box-shadow:0 2px 8px rgba(15,23,42,.07);padding:18px}.hs-card[hidden]{display:none}.hs-badges{display:flex;flex-wrap:wrap;gap:8px}.hs-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:600;text-transform:uppercase}.hs-badge.is-category{background:#ecfdf5;color:#007f5f;border:1px solid #86efc2}.hs-badge.is-listed{background:#fdf2f8;color:var(--pink);border:1px solid #fbcfe8}.hs-card h2{margin:14px 0 0;font-size:22px;line-height:1.2;font-weight:600}.hs-address{display:flex;align-items:flex-start;gap:8px;margin:10px 0 0;color:#52627d;font-size:14px;line-height:1.5}.hs-distance-copy{margin:8px 0 0;color:#64748b;font-size:13px;line-height:1.45}.hs-info-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:14px;padding:12px;border:1px solid #edf2f7;border-radius:8px;background:#f8fafc}.hs-tile span{display:flex;align-items:center;gap:7px;color:#8aa0bd;text-transform:uppercase;font-size:11px;font-weight:600}.hs-tile strong{display:block;margin-top:7px;color:#1f2a44;font-size:14px;font-weight:500;line-height:1.35}.hs-services{margin-top:14px}.hs-services p{margin:0 0 8px;color:#8aa0bd;text-transform:uppercase;font-size:11px;font-weight:600}.hs-service-list{display:flex;flex-wrap:wrap;gap:8px}.hs-service{display:inline-flex;align-items:center;gap:6px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;padding:7px 10px;color:#40516f;font-size:13px;font-weight:500}.hs-card-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}.hs-card-note{margin:10px 0 0;color:#64748b;font-size:12px;line-height:1.45}.hs-side{display:grid;gap:14px;position:sticky;top:104px}.hs-side-card{padding:16px;border:1px solid var(--line);border-radius:10px;background:#fff;box-shadow:0 2px 8px rgba(15,23,42,.06)}.hs-side-card h2{margin:0;font-size:17px;font-weight:600}.hs-side-card p{margin:8px 0 0;color:#52627d;font-size:14px;line-height:1.55}.hs-alert-card{padding:16px;border-radius:10px;background:#071127;color:#fff}.hs-alert-card h2{margin:0;font-size:17px;font-weight:600}.hs-alert-card p{margin:9px 0 0;color:#cbd5e1;font-size:14px;line-height:1.55}.hs-empty{display:none;padding:24px;border:1px dashed #cbd5e1;border-radius:10px;background:#fff;text-align:center;color:#52627d}.hs-empty.is-visible{display:block}@media(max-width:1150px){.hs-hero,.hs-grid{grid-template-columns:1fr}.hs-side{position:static}.hs-note-grid{grid-template-columns:1fr}.hs-info-tiles{grid-template-columns:1fr}}@media(max-width:700px){.hs-title{font-size:24px}.hs-hero{padding:16px}.hs-filter-head{align-items:flex-start;flex-direction:column}.hs-button{width:100%}.hs-card h2{font-size:20px}}
    </style>

    <section class="health-services" aria-label="Local listed maternal healthcare facilities directory">
        <section class="hs-hero">
            <div>
                <p class="hs-kicker">{!! $iconHospital !!} Local Listed Maternal Healthcare Directory</p>
                <h1 class="hs-title">Find Nearby Listed Maternal Healthcare Facilities</h1>
                <p class="hs-copy">This page displays locally listed maternal healthcare facilities around San Pablo City. It does not search all healthcare facilities online.</p>
                <div class="hs-note-grid">
                    <p class="hs-mini-note">GPS only sorts the listed facilities by estimated straight-line distance.</p>
                    <p class="hs-mini-note">Distances are based on latitude and longitude coordinates, not road, travel, traffic, or driving distance.</p>
                    <p class="hs-mini-note">Directions open Google Maps through a normal URL. Project INAY does not use a paid Google Maps API.</p>
                </div>
            </div>
            <div class="hs-location-box">
                <button class="hs-button" type="button" data-use-location>{!! $iconLocate !!} <span data-location-button-label>Use My Location</span></button>
                <p class="hs-status" data-location-status>Enable GPS to sort the listed maternal healthcare facilities by estimated straight-line distance.</p>
                <p class="hs-location-note"><strong>Location is used only to sort listed facilities by estimated straight-line distance.</strong> This does not search all healthcare facilities online.</p>
                <p class="hs-nearest" data-nearest></p>
            </div>
        </section>

        <section class="hs-info">
            <h2>{!! $iconInfo !!} About this facility directory</h2>
            <ul>
                <li>This page displays locally listed maternal healthcare facilities around San Pablo City. It does not search all healthcare facilities online.</li>
                <li>Your GPS location is used only to sort the listed facilities according to their estimated straight-line distance from your location.</li>
                <li>The estimated distance is based on latitude and longitude coordinates. It is not the actual road, travel, or driving distance.</li>
                <li>The Get Directions button opens Google Maps through a normal directions URL. The system does not use a paid Google Maps API.</li>
            </ul>
        </section>

        <section class="hs-filter-panel">
            <div class="hs-filter-head">
                <div>
                    <p class="hs-filter-title">{!! $iconFilter !!} Facility Categories</p>
                    <p class="hs-result-count"><span data-result-count>{{ count($healthFacilities) }}</span> listed result<span data-result-plural>{{ count($healthFacilities) === 1 ? '' : 's' }}</span></p>
                </div>
                <div class="hs-filters" role="group" aria-label="Listed facility category filters">
                    <button class="hs-chip is-active" type="button" data-category="all">All Facilities <span data-count="all">{{ count($healthFacilities) }}</span></button>
                    @foreach($facilityCategories as $category)
                        <button class="hs-chip" type="button" data-category="{{ $category['key'] }}">{{ $category['label'] }} <span data-count="{{ $category['key'] }}">0</span></button>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="hs-grid">
            <div>
                <div class="hs-list" data-facility-list>
                    @foreach($healthFacilities as $facility)
                        @php
                            $services = $facility['maternal_services'] ?? [];
                            $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination='.$facility['latitude'].','.$facility['longitude'];
                        @endphp
                        <article class="hs-card" data-facility-id="{{ $facility['id'] }}" data-category="{{ $facility['category_key'] }}" data-latitude="{{ $facility['latitude'] }}" data-longitude="{{ $facility['longitude'] }}" data-name="{{ $facility['name'] }}">
                            <div class="hs-badges">
                                <span class="hs-badge is-category">{{ $facility['category'] ?? 'Listed Facility' }}</span>
                                <span class="hs-badge is-listed" data-listed-label>Listed Maternal Healthcare Facility</span>
                            </div>
                            <h2>{{ $facility['name'] }}</h2>
                            <p class="hs-address">{!! $iconMapPin !!}<span>{{ $facility['address'] ?? 'Address not available' }}</span></p>
                            <p class="hs-distance-copy" data-distance-copy>Estimated distance: available after GPS permission. This is a straight-line estimate only.</p>
                            <div class="hs-info-tiles">
                                <div class="hs-tile"><span>{!! $iconMapPin !!} Estimated Distance</span><strong data-distance>Available after GPS</strong></div>
                                <div class="hs-tile"><span>{!! $iconPhone !!} Contact / Hotline</span><strong>{{ $facility['hotline'] ?: 'Contact information not available' }}</strong></div>
                                <div class="hs-tile"><span>{!! $iconClock !!} Hours of Care</span><strong>{{ $facility['hours'] ?: 'Hours not specified' }}</strong></div>
                            </div>
                            <div class="hs-services">
                                <p>Available maternal healthcare services</p>
                                <div class="hs-service-list">
                                    @forelse($services as $service)
                                        <span class="hs-service">{!! $iconShield !!} {{ $service }}</span>
                                    @empty
                                        <span class="hs-service">Services not specified</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="hs-card-actions">
                                <a class="hs-button" href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer">{!! $iconNavigation !!} Get Directions</a>
                                @if(!empty($facility['tel']))
                                    <a class="hs-button is-outline" href="tel:{{ $facility['tel'] }}">{!! $iconPhone !!} Call Facility</a>
                                @endif
                            </div>
                            <p class="hs-card-note">Directions open Google Maps through a standard browser URL only. No Google Maps API key is required.</p>
                        </article>
                    @endforeach
                </div>
                <div class="hs-empty" data-empty>No local listed facilities in this category. Project INAY only displays facilities stored locally in the project.</div>
            </div>

            <aside class="hs-side">
                <section class="hs-side-card">
                    <h2>Directory Source</h2>
                    <p>All facilities shown here are locally listed in Project INAY. Filtering and distance sorting happen on this page without live online facility search.</p>
                </section>
                <section class="hs-alert-card">
                    <h2>Emergency Reminder</h2>
                    <p>For severe bleeding, intense abdominal pain, seizures, fainting, or difficulty breathing, call emergency services or go to the nearest emergency facility.</p>
                </section>
            </aside>
        </div>
    </section>

    <script>
        (() => {
            const facilities = Array.from(document.querySelectorAll('[data-facility-id]'));
            const chips = Array.from(document.querySelectorAll('[data-category]'));
            const list = document.querySelector('[data-facility-list]');
            const empty = document.querySelector('[data-empty]');
            const resultCount = document.querySelector('[data-result-count]');
            const resultPlural = document.querySelector('[data-result-plural]');
            const locationButton = document.querySelector('[data-use-location]');
            const locationButtonLabel = document.querySelector('[data-location-button-label]');
            const locationStatus = document.querySelector('[data-location-status]');
            const nearest = document.querySelector('[data-nearest]');
            let activeCategory = 'all';
            let userLocation = null;

            const counts = facilities.reduce((next, card) => {
                const category = card.dataset.category;
                next[category] = (next[category] || 0) + 1;
                return next;
            }, { all: facilities.length });

            document.querySelectorAll('[data-count]').forEach((count) => {
                count.textContent = counts[count.dataset.count] || 0;
            });

            const toRadians = (value) => (value * Math.PI) / 180;
            const distanceKm = (origin, card) => {
                const destinationLat = Number(card.dataset.latitude);
                const destinationLng = Number(card.dataset.longitude);
                const earthRadiusKm = 6371;
                const latDistance = toRadians(destinationLat - origin.latitude);
                const lngDistance = toRadians(destinationLng - origin.longitude);
                const originLat = toRadians(origin.latitude);
                const facilityLat = toRadians(destinationLat);
                const haversine = Math.sin(latDistance / 2) ** 2 + Math.cos(originLat) * Math.cos(facilityLat) * Math.sin(lngDistance / 2) ** 2;
                return earthRadiusKm * 2 * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));
            };

            const formatDistance = (km) => {
                if (km === null || Number.isNaN(km)) return 'Available after GPS';
                if (km < 1) return `Estimated distance: ${Math.round(km * 1000)} m`;
                return `Approximately ${km.toFixed(1)} km away`;
            };

            const updateFacilities = () => {
                facilities.forEach((card) => {
                    const visible = activeCategory === 'all' || card.dataset.category === activeCategory;
                    card.hidden = !visible;
                });

                if (userLocation) {
                    facilities
                        .map((card) => ({ card, distance: distanceKm(userLocation, card) }))
                        .sort((a, b) => a.distance - b.distance)
                        .forEach(({ card, distance }, index) => {
                            card.dataset.distance = distance;
                            card.querySelector('[data-distance]').textContent = formatDistance(distance);
                            card.querySelector('[data-distance-copy]').textContent = `${formatDistance(distance)}. This is an estimated straight-line GPS distance, not road or driving distance.`;
                            card.querySelector('[data-listed-label]').textContent = index === 0 ? 'Nearest Listed Maternal Healthcare Facility' : 'Listed Maternal Healthcare Facility';
                            list.appendChild(card);
                        });

                    const nearestCard = facilities.slice().sort((a, b) => Number(a.dataset.distance || 999999) - Number(b.dataset.distance || 999999))[0];
                    if (nearestCard) {
                        nearest.textContent = `Nearest Listed Maternal Healthcare Facility: ${nearestCard.dataset.name}`;
                        nearest.classList.add('is-visible');
                    }
                }

                const visibleCount = facilities.filter((card) => !card.hidden).length;
                resultCount.textContent = visibleCount;
                resultPlural.textContent = visibleCount === 1 ? '' : 's';
                empty.classList.toggle('is-visible', visibleCount === 0);
            };

            chips.forEach((chip) => {
                chip.addEventListener('click', () => {
                    activeCategory = chip.dataset.category;
                    chips.forEach((item) => item.classList.toggle('is-active', item === chip));
                    updateFacilities();
                });
            });

            locationButton?.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    locationStatus.textContent = 'Location is not supported on this device or browser. The local listed facility directory is still available.';
                    locationStatus.classList.add('is-error');
                    return;
                }

                locationButton.disabled = true;
                locationButtonLabel.textContent = 'Finding Location...';
                locationStatus.textContent = 'Requesting GPS permission for local listed-facility sorting...';
                locationStatus.classList.remove('is-error');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userLocation = {
                            latitude: Number(position.coords.latitude.toFixed(6)),
                            longitude: Number(position.coords.longitude.toFixed(6)),
                        };
                        locationStatus.textContent = 'GPS enabled. Listed maternal healthcare facilities are sorted by estimated straight-line distance.';
                        locationButton.disabled = false;
                        locationButtonLabel.textContent = 'Use My Location';
                        updateFacilities();
                    },
                    (error) => {
                        locationStatus.textContent = error.message || 'Location permission was denied. The listed facility directory is still available without distance sorting.';
                        locationStatus.classList.add('is-error');
                        locationButton.disabled = false;
                        locationButtonLabel.textContent = 'Use My Location';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            });

            updateFacilities();
        })();
    </script>
@endsection