import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../api/api_exception.dart';
import '../models/dto.dart';
import 'app_providers.dart';

class CartState {
  const CartState({this.cart = const Cart(), this.loading = false, this.error});
  final Cart cart;
  final bool loading;
  final String? error;
}

final cartProvider =
    StateNotifierProvider<CartNotifier, CartState>((ref) => CartNotifier(ref));

class CartNotifier extends StateNotifier<CartState> {
  CartNotifier(this._ref) : super(const CartState()) {
    _refresh();
  }
  final Ref _ref;

  Future<void> _refresh() async {
    try {
      final cart = await _ref.read(orderRepositoryProvider).cart();
      state = CartState(cart: cart);
    } on ApiException catch (e) {
      state = CartState(error: e.message);
    }
  }

  Future<bool> add(Product p, {int qty = 1, List<int> optionValueIds = const [], String? note}) async {
    if (state.cart.items.isNotEmpty && state.cart.merchantId != null) {
      // Single-merchant cart is enforced server-side too; caller should have
      // checked. We still attempt and surface the 422 message.
    }
    state = state.copyWith(loading: true);
    try {
      final cart = await _ref.read(orderRepositoryProvider)
          .addItem(p.id, qty: qty, optionValueIds: optionValueIds, note: note);
      state = CartState(cart: cart);
      return true;
    } on ApiException catch (e) {
      state = CartState(cart: state.cart, error: e.message);
      return false;
    }
  }

  Future<void> setQty(String uid, int qty) async {
    if (qty <= 0) return remove(uid);
    try {
      final cart = await _ref.read(orderRepositoryProvider).updateItem(uid, qty);
      state = CartState(cart: cart);
    } on ApiException catch (e) {
      state = CartState(cart: state.cart, error: e.message);
    }
  }

  Future<void> remove(String uid) async {
    try {
      final cart = await _ref.read(orderRepositoryProvider).removeItem(uid);
      state = CartState(cart: cart ?? const Cart());
    } on ApiException catch (e) {
      state = CartState(cart: state.cart, error: e.message);
    }
  }

  Future<String?> applyPromo(String code) async {
    try {
      final cart = await _ref.read(orderRepositoryProvider).applyPromo(code);
      state = CartState(cart: cart);
      return null;
    } on ApiException catch (e) {
      state = CartState(cart: state.cart, error: e.message);
      return e.message;
    }
  }

  void clearError() => state = CartState(cart: state.cart);
}

extension on CartState {
  CartState copyWith({Cart? cart, bool? loading, String? error}) =>
      CartState(cart: cart ?? this.cart, loading: loading ?? this.loading, error: error ?? this.error);
}

final addressesProvider = FutureProvider<List<AddressDto>>((ref) async {
  final list = await ref.watch(orderRepositoryProvider).addresses();
  list.sort((a, b) => b.isDefault.compareTo(a.isDefault));
  return list;
});

final ordersListProvider = FutureProvider.family<List<Order>, bool>((ref, active) async {
  final list = await ref.watch(orderRepositoryProvider).orders(active: active);
  return list;
});

final orderDetailProvider = FutureProvider.family<Order, int>((ref, id) async {
  return ref.watch(orderRepositoryProvider).order(id);
});
