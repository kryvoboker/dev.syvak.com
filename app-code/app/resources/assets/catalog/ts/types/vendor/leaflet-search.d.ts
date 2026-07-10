declare module 'leaflet-search' {
    import * as L from 'leaflet';

    export interface SearchControlOptions extends L.ControlOptions {
        layer?: L.LayerGroup | undefined;
        propertyName?: string | undefined;
        marker?: boolean | undefined;
        initial?: boolean | undefined;
        position?: L.ControlPosition | undefined;
        textPlaceholder?: string | undefined;
        moveToLocation?: ((latlng: L.LatLng, title: string, map: L.Map) => void) | undefined;
    }

    class SearchControl extends L.Control {
        constructor(options?: SearchControlOptions);
    }

    export default SearchControl;
}
