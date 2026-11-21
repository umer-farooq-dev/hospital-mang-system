<?php

namespace App\Http\Controllers;

use App\Models\ItemPrice;
use App\Models\Module;
use App\Models\PriceList;
use App\Models\PricePeriod;
use App\Repositories\PriceListRepository;
use Flash;
use Illuminate\Http\Request;

/**
 * Price lists and surcharge periods (Hospital Charges > Price lists), and the tariff grid.
 */
class PriceListController extends AppBaseController
{
    /** @var PriceListRepository */
    private $priceListRepository;

    public function __construct(PriceListRepository $priceListRepo)
    {
        $this->priceListRepository = $priceListRepo;
    }

    public function index()
    {
        $lists = PriceList::orderBy('sort_order')->get();
        $periods = PricePeriod::orderBy('id')->get();

        return view('price_lists.index', compact('lists', 'periods'));
    }

    public function update(Request $request)
    {
        $this->priceListRepository->saveLists((array) $request->input('lists', []), $request->input('new'), $request->input('default_id'));
        Flash::success(__('messages.price_list.price_lists').' '.__('messages.common.updated_successfully'));

        return redirect(route('price-lists.index'));
    }

    public function updatePeriods(Request $request)
    {
        $this->priceListRepository->savePeriods((array) $request->input('periods', []), $request->input('new_period'));
        Flash::success(__('messages.price_list.periods').' '.__('messages.common.updated_successfully'));

        return redirect(route('price-lists.index'));
    }

    public function tariff(Request $request)
    {
        // Same on/off switch as the price lists screen (Settings > Modules).
        abort_unless(Module::where('route', 'price-lists.index')->where('is_active', 1)->exists(), 404);
        $type = array_key_exists($request->get('type'), ItemPrice::ITEM_TYPES) ? $request->get('type') : array_key_first(ItemPrice::ITEM_TYPES);
        $search = trim((string) $request->get('search')) ?: null;
        $items = $this->priceListRepository->tariffItems($type, $search);
        $default = PriceList::defaultList();
        $lists = PriceList::where('is_active', true)->where('id', '!=', $default?->id)->orderBy('sort_order')->get();

        return view('price_lists.tariff', compact('type', 'search', 'items', 'default', 'lists'));
    }

    public function updateTariff(Request $request)
    {
        $type = $request->input('type');
        abort_unless(array_key_exists($type, ItemPrice::ITEM_TYPES), 404);
        $this->priceListRepository->saveTariff($type, (array) $request->input('rows', []));
        Flash::success(__('messages.price_list.tariff').' '.__('messages.common.updated_successfully'));

        return redirect(route('price-lists.tariff', array_filter(['type' => $type, 'search' => $request->input('search'), 'page' => $request->input('page')])));
    }
}
