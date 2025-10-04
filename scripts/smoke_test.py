"""Simple smoke tester for local dev server routes.
Run this while the dev server is running (http://127.0.0.1:5000).
It checks a list of routes and searches for expected strings.
"""
import requests

ROUTES = [
    ("/", 200, ["تسجيل الدخول", "إنشاء حساب"]),
    ("/login", 200, ["تسجيل الدخول", "البريد أو الجوال"]),
    ("/signup", 200, ["إنشاء حساب", "البريد الإلكتروني"]),
    ("/forgot", 200, ["استعادة كلمة المرور"]),
    ("/reset/invalid-token", 302, None),
    ("/contracts", 302, None),
    ("/dashboard", 302, None),
    ("/contracts/create", 302, None),
    ("/contracts/list", 302, None),
]

BASE = "http://127.0.0.1:5000"

if __name__ == '__main__':
    ok = True
    for path, expected_status, expected_contains in ROUTES:
        url = BASE + path
        try:
            r = requests.get(url, allow_redirects=False, timeout=5)
        except Exception as e:
            print(f"ERROR: {path} -> request failed: {e}")
            ok = False
            continue
        if r.status_code != expected_status:
            print(f"FAIL: {path} -> status {r.status_code} (expected {expected_status})")
            ok = False
        else:
            print(f"OK: {path} -> status {r.status_code}")
            if expected_contains:
                txt = r.text
                for s in expected_contains:
                    if s not in txt:
                        print(f"  MISSING: expected string not found on {path}: {s}")
                        ok = False
        
    if ok:
        print("ALL CHECKS PASSED")
    else:
        print("SOME CHECKS FAILED")
