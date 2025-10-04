#!/usr/bin/env python3
"""Create a test user for local development.

Usage (from project root, with venv active):
  python create_user.py

This will create admin@example.com / Password123 and set role=manager.
"""
from models import init_db, create_user, get_session, User


def main():
    init_db()
    email = "admin@example.com"
    password = "Password123"
    ok, msg = create_user(name="Admin", phone=None, email=email, password=password)
    print("create_user:", ok, msg)
    if ok:
        s = get_session()
        try:
            u = s.query(User).filter_by(email=email).first()
            if u:
                u.role = "manager"
                s.commit()
                print(f"Set role=manager for {email}")
        finally:
            s.close()


if __name__ == "__main__":
    main()
