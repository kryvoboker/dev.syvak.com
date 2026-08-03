import { getDataset } from '@ts-shared/lib/helpers.ts';
import type { InputChoice } from 'choices.js';
import Choices, { type Options } from 'choices.js';
import type { CheckoutBranchSearchItem, CheckoutCitySearchItem } from './checkoutData.ts';

interface ChoiceSettings {
    allowHTML: boolean;
    searchFields: string[];
    shouldSort: boolean;
}

export const buildChoiceItem = (city: CheckoutCitySearchItem): InputChoice => ({
    value: city.city_description,
    label: city.city_description,
    customProperties: {
        city_description: city.city_description,
        nova_poshta_city_id: city.nova_poshta_city_id,
        ukr_poshta_city_id: city.ukr_poshta_city_id,
        city_lat: city.city_lat,
        city_lng: city.city_lng,
    },
});

export const buildBranchChoiceItem = (branch: CheckoutBranchSearchItem): InputChoice => ({
    value: branch.branch_value,
    label: branch.branch_label,
    customProperties: {
        id: branch.id,
        ref: branch.ref,
        city_ref: branch.city_ref,
        postcode: branch.postcode,
        pdcity_id: branch.pdcity_id,
        description: branch.description,
        city_description: branch.city_description,
        city_name: branch.city_name,
        region_ua: branch.region_ua,
        district_ua: branch.district_ua,
        latitude: branch.latitude,
        longitude: branch.longitude,
        schedule: branch.schedule,
        number: branch.number,
        site_key: branch.site_key,
        lock_code: branch.lock_code,
        delivery_method: branch.delivery_method,
        branch_value: branch.branch_value,
        branch_label: branch.branch_label,
    },
});

export const createCityChoices = (element: HTMLSelectElement, emptyText: string, searchFloor: number): Choices => {
    return new Choices(element, {
        allowHTML: false,
        duplicateItemsAllowed: false,
        itemSelectText: '',
        noChoicesText: emptyText,
        noResultsText: emptyText,
        position: 'auto',
        renderChoiceLimit: 100,
        searchEnabled: true,
        searchChoices: false,
        searchFloor,
        searchPlaceholderValue: getDataset(element, 'placeholder') ?? '',
        searchResultLimit: 100,
        shouldSort: false,
    } as Partial<Options> & ChoiceSettings);
};

export const createBranchChoices = (element: HTMLSelectElement, emptyText: string, searchFloor: number): Choices => {
    return new Choices(element, {
        allowHTML: false,
        duplicateItemsAllowed: false,
        itemSelectText: '',
        noChoicesText: emptyText,
        noResultsText: emptyText,
        position: 'auto',
        renderChoiceLimit: 100,
        searchEnabled: true,
        searchChoices: true,
        searchFields: ['label'],
        searchFloor,
        searchPlaceholderValue: getDataset(element, 'placeholder') ?? '',
        searchResultLimit: 100,
        shouldSort: false,
    } as Partial<Options> & ChoiceSettings);
};
