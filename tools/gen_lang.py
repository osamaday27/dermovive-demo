#!/usr/bin/env python3
"""Generates lang/{en,fr,ar}/messages.php + validation.php from tools/translations.txt and
verifies every t('...') key used in the code exists (static keys) — fails the build if one is missing."""
import re, glob, os, sys
root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
rows = {}
for ln in open(f'{root}/tools/translations.txt', encoding='utf-8'):
    ln = ln.rstrip('\n')
    if not ln.strip(): continue
    p = ln.split('|')
    assert len(p) == 4, f'bad line: {ln}'
    rows[p[0]] = p[1:]
def nest(idx):
    d = {}
    for k, v in rows.items():
        cur = d; parts = k.split('.')
        for x in parts[:-1]: cur = cur.setdefault(x, {})
        cur[parts[-1]] = v[idx]
    return d
def php(v, ind=0):
    pad = '    ' * ind
    if isinstance(v, dict):
        s = '[\n'
        for k, x in v.items(): s += f"{pad}    '{k}' => {php(x, ind+1)},\n"
        return s + pad + ']'
    return "'" + v.replace('\\', '\\\\').replace("'", "\\'") + "'"
VAL = {
 'en': {'required':'The :attribute field is required.','email':'The :attribute must be a valid email address.','numeric':'The :attribute must be a number.','integer':'The :attribute must be an integer.','string':'The :attribute must be text.','date':'The :attribute is not a valid date.','unique':'This :attribute is already in use.','exists':'The selected :attribute is invalid.','in':'The selected :attribute is invalid.','min.numeric':'The :attribute must be at least :min.','max.numeric':'The :attribute may not be greater than :max.','max.string':'The :attribute may not be longer than :max characters.','gt.numeric':'The :attribute must be greater than :value.','digits_between':'The :attribute must be between :min and :max digits.','different':'The :attribute and :other must be different.','after':'The :attribute must be a later date.'},
 'fr': {'required':'Le champ :attribute est obligatoire.','email':':attribute doit être une adresse e-mail valide.','numeric':':attribute doit être un nombre.','integer':':attribute doit être un entier.','string':':attribute doit être du texte.','date':":attribute n'est pas une date valide.",'unique':':attribute est déjà utilisé.','exists':':attribute sélectionné est invalide.','in':':attribute sélectionné est invalide.','min.numeric':':attribute doit être au moins :min.','max.numeric':':attribute ne peut pas dépasser :max.','max.string':':attribute ne peut pas dépasser :max caractères.','gt.numeric':':attribute doit être supérieur à :value.','digits_between':':attribute doit contenir entre :min et :max chiffres.','different':':attribute et :other doivent être différents.','after':':attribute doit être une date ultérieure.'},
 'ar': {'required':'حقل :attribute مطلوب.','email':'يجب أن يكون :attribute بريداً إلكترونياً صحيحاً.','numeric':'يجب أن يكون :attribute رقماً.','integer':'يجب أن يكون :attribute عدداً صحيحاً.','string':'يجب أن يكون :attribute نصاً.','date':':attribute ليس تاريخاً صحيحاً.','unique':':attribute مستخدم من قبل.','exists':':attribute المحدد غير صالح.','in':':attribute المحدد غير صالح.','min.numeric':'يجب ألا يقل :attribute عن :min.','max.numeric':'يجب ألا يزيد :attribute عن :max.','max.string':'يجب ألا يتجاوز :attribute :max حرفاً.','gt.numeric':'يجب أن يكون :attribute أكبر من :value.','digits_between':'يجب أن يتكون :attribute من :min إلى :max رقماً.','different':'يجب أن يختلف :attribute عن :other.','after':'يجب أن يكون :attribute تاريخاً لاحقاً.'},
}
def valphp(lang, idx):
    d = {}
    for k, v in VAL[lang].items():
        cur = d; parts = k.split('.')
        for x in parts[:-1]: cur = cur.setdefault(x, {})
        cur[parts[-1]] = v
    attrs = {'form': {k[2:]: v[idx] for k, v in rows.items() if k.startswith('f.')}, 'email': rows['common.email'][idx], 'password': rows['auth.password'][idx],
             'customer_id': rows['nav.customers'][idx], 'date': rows['common.date'][idx], 'amount': rows['common.amount'][idx], 'method': rows['common.method'][idx],
             'treasury_account_id': rows['treas.account'][idx], 'notes': rows['common.notes'][idx]}
    d['attributes'] = attrs
    return d
for i, lang in enumerate(['en', 'fr', 'ar']):
    os.makedirs(f'{root}/overlay/lang/{lang}', exist_ok=True)
    open(f'{root}/overlay/lang/{lang}/messages.php', 'w', encoding='utf-8').write('<?php\n\nreturn ' + php(nest(i)) + ';\n')
    open(f'{root}/overlay/lang/{lang}/validation.php', 'w', encoding='utf-8').write('<?php\n\nreturn ' + php(valphp(lang, i)) + ';\n')
# --- verify usage ---
used = set(); dyn = []
files = glob.glob(f'{root}/overlay/**/*.php', recursive=True)
for f in files:
    if '/lang/' in f: continue
    s = open(f, encoding='utf-8').read()
    for m in re.finditer(r"\bt\('([^']+)'", s):
        k = m.group(1)
        if k.endswith('.') or k.endswith('_'): dyn.append((f, k))
        else: used.add(k)
missing = sorted(k for k in used if k not in rows)
dyn_prefixes = {'common.': ['active','archived'], 'inv.exp_': ['expired','critical','warning','near','valid'], 'rep.tab_': ['sales','profit','inventory','receivables','products','treasury'],
                'rep.p_': ['today','week','month','custom'], 'treas.': ['cash_in','cash_out','transfer'], 'inv.stk_': ['out','low','ok']}
for pre, subs in dyn_prefixes.items():
    for s in subs:
        if pre + s not in rows: missing.append(pre + s)
if missing:
    print('MISSING TRANSLATION KEYS:'); [print(' -', k) for k in missing]; sys.exit(1)
print(f'OK: {len(rows)} keys x 3 languages, {len(used)} static keys verified')
