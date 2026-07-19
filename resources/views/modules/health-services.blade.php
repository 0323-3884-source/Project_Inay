@extends('layouts.app')

@section('title', 'Health Services - Project INAY')
@section('portal_title', 'Health Services')

@php
    $iconHospital = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v4"/><path d="M14 9h-4"/><path d="M18 21V5a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/><path d="M18 11h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h2"/></svg>';
    $iconMapPin = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
    $iconLocate = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v3"/><path d="M12 19v3"/><path d="M2 12h3"/><path d="M19 12h3"/><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/></svg>';
    $iconFilter = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3H2l8 9.5V20l4 2v-9.5L22 3Z"/></svg>';
    $iconPhone = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9Z"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
    $iconShield = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>';
    $iconNavigation = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 19-9-9 19-2-8-8-2Z"/></svg>';
    $iconAlert = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
@endphp

@section('content')
    <style>
        .health-services {
            --hs-pink: #ec0a78;
            --hs-pink-soft: #fff4fa;
            --hs-navy: #061125;
            --hs-text: #26364d;
            --hs-muted: #64748b;
            --hs-line: #dbe5f0;
            --hs-panel: #ffffff;
            --hs-green: #008a61;
            display: grid;
            gap: 16px;
            color: var(--hs-navy);
        }

        .health-services * {
            box-sizing: border-box;
            letter-spacing: 0;
        }

        .health-services svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .hs-hero,
        .hs-filter-panel,
        .hs-card,
        .hs-empty,
        .hs-reminder {
            background: var(--hs-panel);
            border: 1px solid var(--hs-line);
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .hs-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(240px, 330px);
            gap: 18px;
            align-items: stretch;
            padding: 22px;
        }

        .hs-kicker,
        .hs-filter-title,
        .hs-eyebrow,
        .hs-detail span,
        .hs-services p {
            color: var(--hs-muted);
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .hs-kicker,
        .hs-filter-title {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .hs-kicker {
            color: var(--hs-pink);
        }

        .hs-title {
            margin: 8px 0 0;
            max-width: 720px;
            color: #020817;
            font-size: 28px;
            font-weight: 900;
            line-height: 1.12;
        }

        .hs-copy {
            margin: 10px 0 0;
            max-width: 720px;
            color: var(--hs-muted);
            font-size: 14px;
            font-weight: 700;
            line-height: 1.55;
        }

        .hs-summary-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .hs-summary-strip span,
        .hs-badge,
        .hs-service,
        .hs-nearest {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 30px;
            padding: 0 11px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
        }

        .hs-summary-strip span {
            color: var(--hs-text);
            background: #f8fafc;
            border: 1px solid #e7edf5;
        }

        .hs-location-box {
            display: grid;
            gap: 10px;
            align-content: center;
            padding: 16px;
            background: var(--hs-pink-soft);
            border: 1px solid #ffd1e7;
            border-radius: 12px;
        }

        .hs-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 15px;
            color: #ffffff;
            background: var(--hs-pink);
            border: 1px solid var(--hs-pink);
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            transition: background 180ms ease, transform 180ms ease, box-shadow 180ms ease;
        }

        .hs-button:hover {
            background: #d80b78;
            box-shadow: 0 12px 20px rgba(236, 10, 120, 0.16);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .hs-button:disabled {
            background: #f9a8d4;
            border-color: #f9a8d4;
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }

        .hs-button.is-outline {
            color: var(--hs-pink);
            background: #ffffff;
            border-color: #f9a8d4;
        }

        .hs-status,
        .hs-location-note,
        .hs-distance-copy,
        .hs-card-error,
        .hs-reminder p {
            margin: 0;
            color: var(--hs-muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.45;
        }

        .hs-status.is-error,
        .hs-card-error {
            color: #be123c;
        }

        .hs-nearest {
            display: none;
            color: var(--hs-pink);
            background: #ffffff;
            border: 1px solid #ffd1e7;
            text-transform: uppercase;
        }

        .hs-nearest.is-visible {
            display: inline-flex;
        }

        .hs-filter-panel {
            display: grid;
            gap: 12px;
            padding: 16px;
        }

        .hs-filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .hs-result-count {
            margin: 4px 0 0;
            color: var(--hs-muted);
            font-size: 12px;
            font-weight: 800;
        }

        .hs-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hs-chip {
            min-height: 34px;
            padding: 0 12px;
            color: var(--hs-text);
            background: #ffffff;
            border: 1px solid var(--hs-line);
            border-radius: 999px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 900;
            transition: background 180ms ease, border-color 180ms ease, color 180ms ease;
        }

        .hs-chip:hover,
        .hs-chip.is-active {
            color: var(--hs-pink);
            background: var(--hs-pink-soft);
            border-color: var(--hs-pink);
        }

        .hs-list {
            display: grid;
            gap: 12px;
        }

        .hs-card {
            display: grid;
            gap: 13px;
            padding: 18px;
        }

        .hs-card[hidden],
        .hs-card-error[hidden] {
            display: none;
        }

        .hs-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }

        .hs-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .hs-badge {
            min-height: 26px;
            padding: 0 10px;
            font-size: 10px;
            text-transform: uppercase;
        }

        .hs-badge.is-category {
            color: #007f5f;
            background: #ecfdf5;
            border: 1px solid #9cf0c9;
        }

        .hs-badge.is-listed {
            color: var(--hs-pink);
            background: var(--hs-pink-soft);
            border: 1px solid #ffd1e7;
        }

        .hs-card h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            font-weight: 900;
            line-height: 1.2;
        }

        .hs-address {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 8px 0 0;
            color: var(--hs-muted);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.45;
        }

        .hs-distance-pill {
            flex: 0 0 auto;
            min-height: 30px;
            padding: 7px 10px;
            color: var(--hs-text);
            background: #f8fafc;
            border: 1px solid #e7edf5;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
        }

        .hs-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .hs-detail {
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e7edf5;
            border-radius: 10px;
        }

        .hs-detail strong {
            display: block;
            margin-top: 6px;
            color: var(--hs-text);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.35;
        }

        .hs-services {
            display: grid;
            gap: 8px;
        }

        .hs-service-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .hs-service {
            color: var(--hs-text);
            background: #ffffff;
            border: 1px solid #e7edf5;
        }

        .hs-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .hs-empty {
            display: none;
            padding: 24px;
            color: var(--hs-muted);
            text-align: center;
        }

        .hs-empty.is-visible {
            display: block;
        }

        .hs-reminder {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            color: #9f1239;
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .hs-reminder strong {
            display: block;
            margin-bottom: 3px;
            color: #9f1239;
            font-size: 14px;
            font-weight: 900;
        }

        @media (max-width: 760px) {
            .hs-hero,
            .hs-details {
                grid-template-columns: 1fr;
            }

            .hs-hero,
            .hs-card,
            .hs-filter-panel {
                padding: 16px;
            }

            .hs-title {
                font-size: 24px;
            }

            .hs-filter-head,
            .hs-card-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .hs-distance-pill,
            .hs-button {
                width: 100%;
            }
        }
    </style>

    <section class="health-services" aria-label="Local maternal healthcare facilities">
        <section class="hs-hero">
            <div>
                <p class="hs-kicker">{!! $iconHospital !!} Local maternal care directory</p>
                <h1 class="hs-title">Find a listed maternal healthcare facility</h1>
                <p class="hs-copy">Browse Project INAY's local facility list, filter by category, and use your location to sort by nearest estimated distance.</p>
                <div class="hs-summary-strip" aria-label="Directory notes">
                    <span>{{ count($healthFacilities) }} listed facilities</span>
                    <span>San Pablo City area</span>
                    <span>Google Maps directions</span>
                </div>
            </div>
            <div class="hs-location-box">
                <button class="hs-button" type="button" data-use-location>{!! $iconLocate !!} <span data-location-button-label>Use My Location</span></button>
                <p class="hs-status" data-location-status>Enable GPS to sort the local list by estimated distance.</p>
                <p class="hs-location-note">Location is used only for sorting. Distance is an estimate, not travel time.</p>
                <p class="hs-nearest" data-nearest></p>
            </div>
        </section>

        <section class="hs-filter-panel">
            <div class="hs-filter-head">
                <div>
                    <p class="hs-filter-title">{!! $iconFilter !!} Facility categories</p>
                    <p class="hs-result-count"><span data-result-count>{{ count($healthFacilities) }}</span> listed result<span data-result-plural>{{ count($healthFacilities) === 1 ? '' : 's' }}</span></p>
                </div>
            </div>
            <div class="hs-filters" role="group" aria-label="Listed facility category filters">
                <button class="hs-chip is-active" type="button" data-category="all">All</button>
                @foreach($facilityCategories as $category)
                    <button class="hs-chip" type="button" data-category="{{ $category['key'] }}">{{ $category['label'] }}</button>
                @endforeach
            </div>
        </section>

        <div class="hs-list" data-facility-list>
            @foreach($healthFacilities as $facility)
                @php
                    $services = $facility['maternal_services'] ?? [];
                    $visibleServices = collect($services)->take(3);
                    $remainingServices = max(0, count($services) - $visibleServices->count());
                    $latitude = $facility['latitude'] ?? null;
                    $longitude = $facility['longitude'] ?? null;
                    $latitudeText = trim((string) $latitude);
                    $longitudeText = trim((string) $longitude);
                    $hasCoordinateValues = $latitudeText !== '' || $longitudeText !== '';
                    $latitudeNumber = is_numeric($latitude) ? (float) $latitude : null;
                    $longitudeNumber = is_numeric($longitude) ? (float) $longitude : null;
                    $hasValidCoordinates = $latitudeNumber !== null
                        && $longitudeNumber !== null
                        && $latitudeNumber >= -90
                        && $latitudeNumber <= 90
                        && $longitudeNumber >= -180
                        && $longitudeNumber <= 180;
                    $latitudeValue = $hasCoordinateValues ? $latitudeText : '';
                    $longitudeValue = $hasCoordinateValues ? $longitudeText : '';
                    $address = trim((string) ($facility['address'] ?? ''));
                    $directionsUrl = $hasValidCoordinates
                        ? 'https://www.google.com/maps/dir/?api=1&destination='.$latitudeValue.','.$longitudeValue.'&travelmode=driving'
                        : null;
                    $addressDirectionsUrl = (! $hasCoordinateValues && $address !== '')
                        ? 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($address).'&travelmode=driving'
                        : null;
                    $directionsHref = $directionsUrl ?? $addressDirectionsUrl ?? '#';
                @endphp
                <article class="hs-card" data-facility-id="{{ $facility['id'] }}" data-category="{{ $facility['category_key'] }}" data-latitude="{{ $latitudeValue }}" data-longitude="{{ $longitudeValue }}" data-name="{{ $facility['name'] }}">
                    <div class="hs-card-top">
                        <div>
                            <div class="hs-badges">
                                <span class="hs-badge is-category">{{ $facility['category'] ?? 'Listed Facility' }}</span>
                                <span class="hs-badge is-listed" data-listed-label>Listed</span>
                            </div>
                            <h2>{{ $facility['name'] }}</h2>
                            <p class="hs-address">{!! $iconMapPin !!}<span>{{ $facility['address'] ?? 'Address not available' }}</span></p>
                        </div>
                        <span class="hs-distance-pill" data-distance>GPS needed</span>
                    </div>

                    <p class="hs-distance-copy" data-distance-copy>Enable GPS to sort this local list by nearest estimated distance.</p>

                    <div class="hs-details">
                        <div class="hs-detail"><span>{!! $iconPhone !!} Contact</span><strong>{{ $facility['hotline'] ?: 'Not available' }}</strong></div>
                        <div class="hs-detail"><span>{!! $iconClock !!} Hours</span><strong>{{ $facility['hours'] ?: 'Not specified' }}</strong></div>
                    </div>

                    <div class="hs-services">
                        <p>Key services</p>
                        <div class="hs-service-list">
                            @forelse($visibleServices as $service)
                                <span class="hs-service">{!! $iconShield !!} {{ $service }}</span>
                            @empty
                                <span class="hs-service">Services not specified</span>
                            @endforelse
                            @if($remainingServices > 0)
                                <span class="hs-service">+{{ $remainingServices }} more</span>
                            @endif
                        </div>
                    </div>

                    <div class="hs-card-actions">
                        <a class="hs-button" href="{{ $directionsHref }}" target="_blank" rel="noopener noreferrer" data-directions-button data-directions-url="{{ $directionsUrl ?? '' }}" data-address-directions-url="{{ $addressDirectionsUrl ?? '' }}" aria-describedby="directions-error-{{ $facility['id'] }}">{!! $iconNavigation !!} Get Directions</a>
                        @if(!empty($facility['tel']))
                            <a class="hs-button is-outline" href="tel:{{ $facility['tel'] }}">{!! $iconPhone !!} Call</a>
                        @endif
                    </div>
                    <p class="hs-card-error" id="directions-error-{{ $facility['id'] }}" data-directions-error role="alert" hidden></p>
                </article>
            @endforeach
        </div>

        <div class="hs-empty" data-empty>No local listed facilities in this category.</div>

        <section class="hs-reminder">
            <span>{!! $iconAlert !!}</span>
            <div>
                <strong>Emergency reminder</strong>
                <p>For severe bleeding, intense abdominal pain, seizures, fainting, or difficulty breathing, go to the nearest emergency facility immediately.</p>
            </div>
        </section>
    </section>

    <script>
        (() => {
            const directionsUnavailableMessage = 'Location coordinates are unavailable for this healthcare facility.';
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

            const parseFacilityCoordinates = (card) => {
                const latitudeText = (card?.dataset.latitude || '').trim();
                const longitudeText = (card?.dataset.longitude || '').trim();
                const latitude = Number(latitudeText);
                const longitude = Number(longitudeText);
                const hasAnyCoordinateValue = latitudeText !== '' || longitudeText !== '';
                const isValid = Number.isFinite(latitude)
                    && Number.isFinite(longitude)
                    && latitude >= -90
                    && latitude <= 90
                    && longitude >= -180
                    && longitude <= 180;

                return { latitudeText, longitudeText, latitude, longitude, hasAnyCoordinateValue, isValid };
            };

            const coordinateDirectionsUrl = ({ latitudeText, longitudeText }) => `https://www.google.com/maps/dir/?api=1&destination=${latitudeText},${longitudeText}&travelmode=driving`;

            const clearDirectionsError = (button) => {
                const error = button.closest('[data-facility-id]')?.querySelector('[data-directions-error]');
                if (!error) return;

                error.hidden = true;
                error.textContent = '';
            };

            const showDirectionsError = (button) => {
                const error = button.closest('[data-facility-id]')?.querySelector('[data-directions-error]');
                if (!error) return;

                error.textContent = directionsUnavailableMessage;
                error.hidden = false;
            };

            const directionsUrlFor = (button, card) => {
                const coordinates = parseFacilityCoordinates(card);

                if (coordinates.isValid) {
                    return coordinateDirectionsUrl(coordinates);
                }

                if (!coordinates.hasAnyCoordinateValue && button.dataset.addressDirectionsUrl) {
                    return button.dataset.addressDirectionsUrl;
                }

                return null;
            };

            const toRadians = (value) => (value * Math.PI) / 180;

            const distanceKm = (origin, card) => {
                const destination = parseFacilityCoordinates(card);
                if (!destination.isValid) return null;

                const earthRadiusKm = 6371;
                const latDistance = toRadians(destination.latitude - origin.latitude);
                const lngDistance = toRadians(destination.longitude - origin.longitude);
                const originLat = toRadians(origin.latitude);
                const facilityLat = toRadians(destination.latitude);
                const haversine = Math.sin(latDistance / 2) ** 2 + Math.cos(originLat) * Math.cos(facilityLat) * Math.sin(lngDistance / 2) ** 2;
                return earthRadiusKm * 2 * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));
            };

            const formatDistance = (km) => {
                if (km === null || Number.isNaN(km)) return 'GPS needed';
                if (km < 1) return `${Math.round(km * 1000)} m away`;
                return `${km.toFixed(1)} km away`;
            };

            const updateFacilities = () => {
                facilities.forEach((card) => {
                    const visible = activeCategory === 'all' || card.dataset.category === activeCategory;
                    card.hidden = !visible;
                });

                if (userLocation) {
                    const sortedFacilities = facilities
                        .map((card) => ({ card, distance: distanceKm(userLocation, card) }))
                        .sort((a, b) => (a.distance ?? Number.POSITIVE_INFINITY) - (b.distance ?? Number.POSITIVE_INFINITY));

                    sortedFacilities.forEach(({ card, distance }) => {
                        const distanceText = formatDistance(distance);
                        card.dataset.distance = distance ?? '';
                        card.querySelector('[data-distance]').textContent = distanceText;
                        card.querySelector('[data-distance-copy]').textContent = `${distanceText}. Estimated straight-line distance only.`;
                        list.appendChild(card);
                    });

                    const nearestCard = sortedFacilities.find((item) => item.distance !== null)?.card;
                    facilities.forEach((card) => {
                        card.querySelector('[data-listed-label]').textContent = card === nearestCard ? 'Nearest listed' : 'Listed';
                    });

                    if (nearestCard) {
                        nearest.textContent = `Nearest listed facility: ${nearestCard.dataset.name}`;
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

            document.querySelectorAll('[data-directions-button]').forEach((button) => {
                button.addEventListener('click', (event) => {
                    const card = button.closest('[data-facility-id]');
                    const directionsUrl = directionsUrlFor(button, card);

                    event.preventDefault();

                    if (!directionsUrl) {
                        showDirectionsError(button);
                        return;
                    }

                    clearDirectionsError(button);
                    window.open(directionsUrl, '_blank', 'noopener,noreferrer');
                });
            });

            locationButton?.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    locationStatus.textContent = 'Location is not supported on this browser. The facility list is still available.';
                    locationStatus.classList.add('is-error');
                    return;
                }

                locationButton.disabled = true;
                locationButtonLabel.textContent = 'Finding Location...';
                locationStatus.textContent = 'Requesting GPS permission for nearest-list sorting...';
                locationStatus.classList.remove('is-error');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userLocation = {
                            latitude: Number(position.coords.latitude.toFixed(6)),
                            longitude: Number(position.coords.longitude.toFixed(6)),
                        };
                        locationStatus.textContent = 'Location enabled. Facilities are sorted by estimated distance.';
                        locationButton.disabled = false;
                        locationButtonLabel.textContent = 'Use My Location';
                        updateFacilities();
                    },
                    (error) => {
                        locationStatus.textContent = error.message || 'Location permission was denied. The facility list is still available.';
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
