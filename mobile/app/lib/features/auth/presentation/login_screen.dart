import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/providers/app_providers.dart';
import '../../../core/utils/validators.dart';
import '../../../l10n/app_localizations.dart';
import '../../common/presentation/widgets.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});
  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneCtrl = TextEditingController();
  final _codeCtrl = TextEditingController();
  bool _sending = false;
  bool _verifying = false;
  bool _codeSent = false;
  int _cooldown = 0;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    _phoneCtrl.dispose();
    _codeCtrl.dispose();
    super.dispose();
  }

  Future<void> _sendCode() async {
    final cc = ref.read(brandConfigProvider).valueOrNull?.countryCode ?? '+212';
    if (Validators.phone(cc, _phoneCtrl.text) != null) return;
    setState(() => _sending = true);
    try {
      await ref.read(authRepositoryProvider).requestOtp(Validators.normalizedPhone(cc, _phoneCtrl.text));
      setState(() {
        _codeSent = true;
        _cooldown = 60;
      });
      _startCooldown();
    } on ApiException catch (e) {
      toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  void _startCooldown() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_cooldown <= 1) {
        t.cancel();
        if (mounted) setState(() => _cooldown = 0);
      } else {
        if (mounted) setState(() => _cooldown--);
      }
    });
  }

  Future<void> _verify() async {
    final cc = ref.read(brandConfigProvider).valueOrNull?.countryCode ?? '+212';
    setState(() => _verifying = true);
    try {
      final user = await ref.read(sessionProvider.notifier).login(
          Validators.normalizedPhone(cc, _phoneCtrl.text), _codeCtrl.text.trim());
      if (!mounted) return;
      toast(context, S.of(context).welcomeBack(user.name.isEmpty ? user.phone : user.name));
    } on ApiException catch (e) {
      toast(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _verifying = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = S.of(context);
    final brand = ref.watch(brandConfigProvider).valueOrNull;
    final cc = brand?.countryCode ?? '+212';

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Center(child: BrandLogo(size: 84)),
                  const SizedBox(height: 16),
                  Center(
                      child: Text(brand?.appName ?? 'Marsaly',
                          style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900))),
                  Center(
                      child: Text(
                          brand?.taglines[_localeTag(context)] ?? l.appTagline,
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.bodyMedium)),
                  const SizedBox(height: 32),
                  TextFormField(
                    controller: _phoneCtrl,
                    keyboardType: TextInputType.phone,
                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9+\s]'))],
                    decoration: InputDecoration(prefixText: '$cc ', labelText: l.phoneLabel, prefixIcon: const Icon(Icons.phone_outlined)),
                    validator: (_) => null, // validation handled client-side like backend
                  ),
                  const SizedBox(height: 16),
                  if (!_codeSent)
                    FilledButton(
                      onPressed: _sending ? null : _sendCode,
                      child: _sending
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                          : Text(l.sendCode),
                    )
                  else ...[
                    TextFormField(
                      controller: _codeCtrl,
                      keyboardType: TextInputType.number,
                      maxLength: 6,
                      autofocus: true,
                      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                      decoration: InputDecoration(labelText: l.codeLabel, prefixIcon: const Icon(Icons.pin_outlined)),
                    ),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: _verifying ? null : _verify,
                      child: _verifying
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                          : Text(l.verify),
                    ),
                    TextButton(
                      onPressed: _cooldown == 0 ? _sendCode : null,
                      child: Text(_cooldown == 0 ? l.sendCode : l.resendIn(_cooldown)),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  String _localeTag(BuildContext context) {
    final loc = Localizations.localeOf(context).languageCode;
    return loc;
  }
}
