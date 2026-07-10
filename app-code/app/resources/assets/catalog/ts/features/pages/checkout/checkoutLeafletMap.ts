import L, { type IconOptions, type Marker } from 'leaflet';
import 'leaflet.markercluster';
import { blockBody, findArrayElems, findElem, isEmpty } from '@ts-shared/lib/helpers.ts';
import SearchControl from 'leaflet-search';

export interface CheckoutMapCity {
    city_description: string;
    city_lat?: number | null;
    city_lng?: number | null;
}

export interface CheckoutMapPoint {
    id: string;
    title: string;
    description: string;
    lat: number;
    lng: number;
    schedule: string | null;
    delivery_method: string;
    branch_value: string;
    city_ref?: string | null;
    city_description?: string | null;
}

export interface CheckoutMapMarkerIcons {
    nova_poshta?: string | null;
    ukr_poshta?: string | null;
}

export interface OpenCheckoutLeafletMapParams {
    city: CheckoutMapCity;
    points: CheckoutMapPoint[];
    selectedPointId?: string | null;
    selectedDeliveryMethod?: string | null;
    markerIcons?: CheckoutMapMarkerIcons | null;
    texts?: {
        title?: string | null;
        search_placeholder?: string | null;
        list_title?: string | null;
        empty?: string | null;
        choose_city_first?: string | null;
        deliver_here?: string | null;
        close?: string | null;
        day_off?: string | null;
    } | null;
}

type DeliveryPointClickCallback = (pointId: string) => void;

interface RenderMapParams extends OpenCheckoutLeafletMapParams {
    callback: DeliveryPointClickCallback;
}

interface MarkerClusterGroup extends L.LayerGroup {
    zoomToShowLayer(layer: Marker, callback: () => void): void;
}

const MAX_ZOOM_LEVEL: number = 19;
const DEFAULT_ZOOM_LEVEL: number = 13;
const CLUSTER_DISABLE_ZOOM: number = 17;
const MAP_ID: string = 'checkout-leaflet-map';
const LIST_ITEM_CLASS_NAMES: string =
    'flex w-full flex-col gap-1 rounded-xl border border-white/10 bg-white/5 p-3 text-left transition-colors hover:border-white/30 hover:bg-white/10';
const LIST_ACTIVE_CLASS_NAMES: string[] = ['border-white/40', 'bg-white/15'];
const DELIVERY_BUTTON_CLASS_NAMES: string = 'checkout__delivery-here-btn simple-buy-btn mt-3 w-full';

let activeMapInstance: L.Map | null = null;

const escapeHtml = (value: string): string => {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const getText = (value: string | null | undefined, fallback: string): string => {
    const normalizedValue = String(value ?? '').trim();

    return normalizedValue === '' ? fallback : normalizedValue;
};

const buildModalMarkup = (params: OpenCheckoutLeafletMapParams): string => {
    const title = escapeHtml(getText(params.texts?.title, 'Choose delivery point'));
    const searchPlaceholder = escapeHtml(getText(params.texts?.search_placeholder, 'Search by name or address'));
    const listTitle = escapeHtml(getText(params.texts?.list_title, 'Available delivery points'));
    const closeText = escapeHtml(getText(params.texts?.close, 'Close'));

    return [
        `<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-2 md:p-4" data-checkout-leaflet-modal>`,
        '<div class="absolute inset-0" data-checkout-leaflet-backdrop></div>',
        '<div class="relative flex h-[calc(100vh-1rem)] w-full max-w-7xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-[#0f1115] text-white shadow-2xl md:h-[calc(100vh-2rem)]">',
        '<div class="flex items-start justify-between gap-4 border-b border-white/10 px-4 py-4 md:px-6">',
        `<div class="min-w-0"><p class="text-xs uppercase tracking-[0.24em] text-white/50">${title}</p><p class="mt-1 text-sm text-white/70">${listTitle}</p></div>`,
        `<button class="inline-flex size-10 items-center justify-center rounded-full border border-white/10 text-white transition-colors hover:bg-white/10" type="button" data-checkout-leaflet-close aria-label="${closeText}"><span class="icon-[mdi--close] size-5"></span></button>`,
        '</div>',
        '<div class="grid min-h-0 flex-1 gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_22rem] lg:p-6">',
        '<div class="min-h-96 overflow-hidden rounded-2xl border border-white/10 bg-[#151822]">',
        `<div id="${MAP_ID}" class="size-full min-h-96"></div>`,
        '</div>',
        '<div class="flex min-h-0 flex-col gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">',
        '<label class="flex items-center gap-2 rounded-xl border border-white/10 bg-black/20 px-3 py-2 text-sm text-white/60">',
        '<span class="icon-[tabler--search] size-4 shrink-0"></span>',
        `<input class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-white placeholder:text-white/40 focus:outline-none" type="search" data-checkout-leaflet-search placeholder="${searchPlaceholder}">`,
        '</label>',
        `<div class="text-sm font-medium uppercase tracking-[0.08em] text-white/70">${listTitle}</div>`,
        `<div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1" data-checkout-leaflet-list></div>`,
        '</div>',
        '</div>',
        '</div>',
        '</div>',
    ].join('');
};

const createMarkerIcon = (
    point: CheckoutMapPoint,
    markerIcons: CheckoutMapMarkerIcons | null | undefined,
): L.Icon | undefined => {
    const iconUrl = point.delivery_method === 'ukr_poshta' ? markerIcons?.ukr_poshta : markerIcons?.nova_poshta;

    if (isEmpty(iconUrl)) {
        return undefined;
    }

    const iconOptions: IconOptions = {
        iconUrl: String(iconUrl),
        iconSize: [40, 52],
        iconAnchor: [20, 52],
        popupAnchor: [0, -46],
        tooltipAnchor: [0, -38],
    };

    return L.icon(iconOptions);
};

const buildPopupHtml = (point: CheckoutMapPoint, deliverHereText: string): string => {
    const scheduleHtml = point.schedule ? `<div class="text-base leading-6 text-black/75">${point.schedule}</div>` : '';

    return [
        '<div class="flex w-full flex-col gap-3">',
        `<div class="text-xl font-semibold text-black">${escapeHtml(point.title)}</div>`,
        scheduleHtml,
        `<button class="${DELIVERY_BUTTON_CLASS_NAMES}" type="button" data-map-delivery-point-id="${escapeHtml(point.id)}">${escapeHtml(deliverHereText)}</button>`,
        '</div>',
    ].join('');
};

const createListItemHtml = (point: CheckoutMapPoint, deliverHereText: string): string => {
    const scheduleHtml = point.schedule ? `<div class="text-sm leading-6 text-white/75">${point.schedule}</div>` : '';

    return [
        `<article class="${LIST_ITEM_CLASS_NAMES}" data-checkout-leaflet-item data-checkout-leaflet-item-id="${escapeHtml(point.id)}" data-search-text="${escapeHtml(`${point.title} ${point.description} ${point.schedule ?? ''}`.trim().toLowerCase())}">`,
        `<button class="text-left text-xl font-semibold text-white" type="button" data-checkout-leaflet-focus>${escapeHtml(point.title)}</button>`,
        scheduleHtml,
        `<button class="${DELIVERY_BUTTON_CLASS_NAMES}" type="button" data-map-delivery-point-id="${escapeHtml(point.id)}">${escapeHtml(deliverHereText)}</button>`,
        '</article>',
    ].join('');
};

const renderListItems = (
    listEl: HTMLDivElement | null,
    points: CheckoutMapPoint[],
    texts: Required<NonNullable<OpenCheckoutLeafletMapParams['texts']>>,
): void => {
    if (!listEl) {
        return;
    }

    if (points.length === 0) {
        listEl.innerHTML = `<div class="rounded-xl border border-dashed border-white/15 px-4 py-6 text-sm text-white/60">${escapeHtml(getText(texts.empty, 'No delivery points available'))}</div>`;

        return;
    }

    listEl.innerHTML = points
        .map((point: CheckoutMapPoint): string =>
            createListItemHtml(
                point,
                getText(texts.deliver_here, 'Deliver here'),
            ),
        )
        .join('');
};

const bindPopupButtons = (callback: DeliveryPointClickCallback): void => {
    findArrayElems('[data-map-delivery-point-id]').forEach((button: HTMLElement): void => {
        button.addEventListener(
            'click',
            (): void => {
                const pointId = button.dataset.mapDeliveryPointId ?? '';

                if (pointId === '') {
                    return;
                }

                callback(pointId);
            },
            { once: true },
        );
    });
};

const setActiveListItem = (pointId: string): void => {
    findArrayElems('[data-checkout-leaflet-item]').forEach((item: HTMLElement): void => {
        LIST_ACTIVE_CLASS_NAMES.forEach((className: string): void => {
            item.classList.toggle(className, item.dataset.checkoutLeafletItemId === pointId);
        });
    });
};

const scrollListItemIntoView = (pointId: string): void => {
    const item = <HTMLElement | null>findElem(`[data-checkout-leaflet-item-id="${pointId}"]`);

    item?.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });
};

const closeModal = (modalEl: HTMLDivElement): void => {
    try {
        activeMapInstance?.off();
        activeMapInstance?.remove();
    } catch {
        // Ignore cleanup failures.
    }

    activeMapInstance = null;
    blockBody(false);
    modalEl.remove();
};

const initializeMap = (params: RenderMapParams, modalEl: HTMLDivElement): void => {
    const mapContainer = <HTMLDivElement | null>findElem(`#${MAP_ID}`, modalEl);

    if (!mapContainer || params.points.length === 0) {
        return;
    }

    const cityLat = Number(params.city.city_lat ?? 0);
    const cityLng = Number(params.city.city_lng ?? 0);
    const firstPoint = params.points[0];
    const center: L.LatLngTuple =
        Number.isFinite(cityLat) && Number.isFinite(cityLng) && cityLat !== 0 && cityLng !== 0
            ? [cityLat, cityLng]
            : [firstPoint.lat, firstPoint.lng];

    const map = L.map(mapContainer, {
        center,
        zoom: DEFAULT_ZOOM_LEVEL,
        preferCanvas: true,
        closePopupOnClick: true,
    });

    activeMapInstance = map;

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: MAX_ZOOM_LEVEL,
        attribution: '&copy; OpenStreetMap contributors & CartoDB',
    }).addTo(map);

    const clusters = (
        L as unknown as { markerClusterGroup: (options: Record<string, unknown>) => MarkerClusterGroup }
    ).markerClusterGroup({
        disableClusteringAtZoom: CLUSTER_DISABLE_ZOOM,
    });
    const markerById = new Map<string, Marker>();
    const listItemById = new Map<string, HTMLElement>();

    const selectPoint = (pointId: string): void => {
        params.callback(pointId);
        closeModal(modalEl);
    };

    const updatePopupButtonBindings = (): void => {
        bindPopupButtons(selectPoint);
    };

    params.points.forEach((point: CheckoutMapPoint): void => {
        const marker = L.marker([point.lat, point.lng], {
            title: point.title,
            icon: createMarkerIcon(point, params.markerIcons),
        }).bindPopup(
            buildPopupHtml(
                point,
                params.texts?.deliver_here ?? 'Deliver here',
            ),
        );

        markerById.set(point.id, marker);
        clusters.addLayer(marker);

        marker.on('click', (): void => {
            setActiveListItem(point.id);
            scrollListItemIntoView(point.id);
        });

        marker.on('popupopen', updatePopupButtonBindings);
    });

    map.addLayer(clusters);

    const search = new SearchControl({
        layer: clusters as unknown as L.LayerGroup,
        propertyName: 'title',
        marker: false,
        initial: false,
        position: 'topleft',
        textPlaceholder: params.texts?.search_placeholder ?? 'Search by name or address',
        moveToLocation: (latlng: L.LatLng, _title: string, targetMap: L.Map): void => {
            targetMap.setView(latlng, Math.max(targetMap.getZoom(), MAX_ZOOM_LEVEL), { animate: true });
        },
    });

    (map as unknown as L.Map & { on(event: 'search:locationfound', fn: (event: { layer: Marker }) => void): void }).on(
        'search:locationfound',
        (event: { layer: Marker }): void => {
            const marker = event.layer;

            for (const [pointId, currentMarker] of markerById.entries()) {
                if (currentMarker === marker) {
                    clusters.zoomToShowLayer(marker, (): void => {
                        const latlng = marker.getLatLng();

                        map.setView(latlng, Math.max(map.getZoom(), MAX_ZOOM_LEVEL), { animate: true });
                        setActiveListItem(pointId);
                        scrollListItemIntoView(pointId);
                        marker.openPopup();
                    });

                    break;
                }
            }
        },
    );

    map.addControl(search);

    const focusPoint = (pointId: string): void => {
        const marker = markerById.get(pointId);

        if (!marker) {
            return;
        }

        clusters.zoomToShowLayer(marker, (): void => {
            const latlng = marker.getLatLng();

            map.setView(latlng, Math.max(map.getZoom(), MAX_ZOOM_LEVEL), { animate: true });
            marker.openPopup();
            setActiveListItem(pointId);
            scrollListItemIntoView(pointId);
        });
    };

    renderListItems(<HTMLDivElement | null>findElem('[data-checkout-leaflet-list]', modalEl), params.points, {
        title: params.texts?.title ?? 'Choose delivery point',
        search_placeholder: params.texts?.search_placeholder ?? 'Search by name or address',
        list_title: params.texts?.list_title ?? 'Available delivery points',
        empty: params.texts?.empty ?? 'No delivery points available',
        choose_city_first: params.texts?.choose_city_first ?? 'Choose a city first',
        deliver_here: params.texts?.deliver_here ?? 'Deliver here',
        close: params.texts?.close ?? 'Close',
        day_off: params.texts?.day_off ?? 'Closed',
    });

    listItemById.clear();
    params.points.forEach((point: CheckoutMapPoint): void => {
        const listItem = <HTMLElement | null>findElem(`[data-checkout-leaflet-item-id="${point.id}"]`, modalEl);

        if (!listItem) {
            return;
        }

        listItemById.set(point.id, listItem);

        const focusButton = <HTMLButtonElement | null>findElem('[data-checkout-leaflet-focus]', listItem);
        focusButton?.addEventListener('click', (): void => {
            focusPoint(point.id);
        });

        listItem.addEventListener('click', (event: MouseEvent): void => {
            const target = event.target as HTMLElement | null;

            if (target?.closest('[data-map-delivery-point-id]')) {
                return;
            }

            focusPoint(point.id);
        });
    });

    bindPopupButtons(selectPoint);

    const searchInput = <HTMLInputElement | null>findElem('[data-checkout-leaflet-search]', modalEl);

    searchInput?.addEventListener('input', (): void => {
        const searchValue = searchInput.value.trim().toLowerCase();

        listItemById.forEach((listItem: HTMLElement): void => {
            const searchText = String(listItem.dataset.searchText ?? '');
            const isVisible = searchValue === '' || searchText.includes(searchValue);

            listItem.classList.toggle('hidden', !isVisible);
        });
    });

    requestAnimationFrame((): void => {
        map.invalidateSize();
        const selectedPointId = params.selectedPointId ?? params.points[0]?.id ?? null;

        if (selectedPointId !== null) {
            setActiveListItem(selectedPointId);
        }
    });
};

const openModal = (params: RenderMapParams): void => {
    const modalEl = document.createElement('div');
    modalEl.innerHTML = buildModalMarkup(params);

    const modalRoot = <HTMLDivElement | null>findElem('[data-checkout-leaflet-modal]', modalEl);

    if (!modalRoot) {
        return;
    }

    document.body.append(modalRoot);
    blockBody(true);
    modalRoot.dataset.deliveryMethod = params.selectedDeliveryMethod ?? '';

    const closeButtons = <HTMLButtonElement[] | []>findArrayElems('[data-checkout-leaflet-close]', modalRoot);
    const backdrop = <HTMLDivElement | null>findElem('[data-checkout-leaflet-backdrop]', modalRoot);
    let isClosed = false;
    const handleKeyDown = (event: KeyboardEvent): void => {
        if (event.key === 'Escape') {
            cleanup();
        }
    };

    const cleanup = (): void => {
        if (isClosed) {
            return;
        }

        isClosed = true;
        document.removeEventListener('keydown', handleKeyDown);
        closeModal(modalRoot);
    };

    closeButtons.forEach((closeButton: HTMLButtonElement): void => {
        closeButton.addEventListener('click', cleanup, { once: true });
    });

    backdrop?.addEventListener('click', cleanup, { once: true });

    document.addEventListener('keydown', handleKeyDown);

    initializeMap(
        {
            ...params,
            callback: params.callback,
        },
        modalRoot,
    );
};

export const openCheckoutLeafletMap = (
    params: OpenCheckoutLeafletMapParams & { callback: DeliveryPointClickCallback },
): void => {
    if (params.points.length === 0) {
        return;
    }

    const normalizedPoints = params.points.filter(
        (point: CheckoutMapPoint): boolean =>
            Number.isFinite(point.lat) && Number.isFinite(point.lng) && point.title.trim() !== '',
    );

    if (normalizedPoints.length === 0) {
        return;
    }

    openModal({
        ...params,
        points: normalizedPoints,
    });
};
