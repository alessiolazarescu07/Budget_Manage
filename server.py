#!/usr/bin/env python3
"""
Development Server per Alessio Budget
Consente di avviare l'applicazione in locale su macOS/Linux/Windows.
Se PHP è disponibile esegue il server nativo PHP; se PHP non è ancora configurato,
esegue un server HTTP con supporto SQLite nativo in Python!
"""

import sys
import os
import shutil
import subprocess

PORT = 8080

def run_php():
    php_path = shutil.which("php")
    if php_path:
        print(f"[*] Trovato interprete PHP: {php_path}")
        print(f"[+] Avvio server web PHP su http://localhost:{PORT}")
        cmd = [php_path, "-S", f"localhost:{PORT}"]
        try:
            subprocess.run(cmd)
            return True
        except KeyboardInterrupt:
            return True
    return False

def run_python_server():
    import json
    import sqlite3
    from http.server import HTTPServer, SimpleHTTPRequestHandler
    from urllib.parse import urlparse, parse_qs

    DATA_DIR = os.path.join(os.path.dirname(__file__), "data")
    os.makedirs(DATA_DIR, exist_ok=True)
    DB_FILE = os.path.join(DATA_DIR, "budget.sqlite")

    def init_db():
        conn = sqlite3.connect(DB_FILE)
        c = conn.cursor()
        c.execute("""
            CREATE TABLE IF NOT EXISTS transactions (
                id TEXT PRIMARY KEY,
                type TEXT NOT NULL,
                title TEXT NOT NULL,
                amount REAL NOT NULL,
                category TEXT NOT NULL,
                date TEXT NOT NULL,
                account TEXT NOT NULL DEFAULT 'Conto Principale',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS vaults (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                amount REAL NOT NULL DEFAULT 0.0,
                color TEXT NOT NULL DEFAULT 'indigo',
                is_emergency INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS goals (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                target REAL NOT NULL,
                current REAL NOT NULL DEFAULT 0.0,
                deadline TEXT NOT NULL,
                icon TEXT NOT NULL DEFAULT 'fa-piggy-bank',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )
        """)
        c.execute("SELECT COUNT(*) FROM vaults")
        if c.fetchone()[0] == 0:
            c.execute("INSERT INTO vaults (id, name, amount, color, is_emergency) VALUES ('v-emergency', 'Fondo Emergenza', 0.0, 'emerald', 1)")
        conn.commit()
        conn.close()

    init_db()

    class BudgetHandler(SimpleHTTPRequestHandler):
        def do_GET(self):
            parsed = urlparse(self.path)
            if parsed.path in ('/api.php', '/api'):
                params = parse_qs(parsed.query)
                action = params.get('action', [''])[0]
                self.handle_api(action, params, {})
                return
            if parsed.path == '/':
                self.path = '/index.html'
            return super().do_GET()

        def do_POST(self):
            parsed = urlparse(self.path)
            if parsed.path in ('/api.php', '/api'):
                params = parse_qs(parsed.query)
                content_length = int(self.headers.get('Content-Length', 0))
                body = self.rfile.read(content_length).decode('utf-8')
                post_data = {}
                if body:
                    try:
                        post_data = json.loads(body)
                    except Exception:
                        pass
                action = params.get('action', [post_data.get('action', '')])[0]
                self.handle_api(action, params, post_data)
                return
            self.send_error(404, "Not Found")

        def handle_api(self, action, params, post_data):
            conn = sqlite3.connect(DB_FILE)
            conn.row_factory = sqlite3.Row
            c = conn.cursor()

            def send_json(success, data=None, error=None, code=200):
                self.send_response(code)
                self.send_header('Content-Type', 'application/json; charset=utf-8')
                self.end_headers()
                res = {'success': success, 'data': data, 'error': error}
                self.wfile.write(json.dumps(res, ensure_ascii=False).encode('utf-8'))

            try:
                if action == 'get_all':
                    c.execute("SELECT * FROM transactions ORDER BY date DESC, created_at DESC")
                    txs = [dict(r) for r in c.fetchall()]
                    c.execute("SELECT * FROM vaults ORDER BY created_at ASC")
                    vaults = [dict(r) for r in c.fetchall()]
                    for v in vaults:
                        v['isEmergency'] = bool(v.get('is_emergency'))
                    c.execute("SELECT * FROM goals ORDER BY deadline ASC")
                    goals = [dict(r) for r in c.fetchall()]
                    c.execute("SELECT key, value FROM settings")
                    settings = {r['key']: r['value'] for r in c.fetchall()}

                    send_json(True, {
                        'transactions': txs,
                        'vaults': vaults,
                        'goals': goals,
                        'settings': settings,
                        'engine': 'sqlite'
                    })

                elif action == 'save_transaction':
                    tx = post_data
                    tid = tx.get('id') or f"tx-{int(os.times()[4] * 1000)}"
                    c.execute("""
                        INSERT INTO transactions (id, type, title, amount, category, date, account)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON CONFLICT(id) DO UPDATE SET
                            type=excluded.type,
                            title=excluded.title,
                            amount=excluded.amount,
                            category=excluded.category,
                            date=excluded.date,
                            account=excluded.account
                    """, (tid, tx.get('type', 'expense'), tx.get('title'), float(tx.get('amount', 0)),
                          tx.get('category', 'Altro'), tx.get('date'), tx.get('account', 'Conto Principale')))
                    conn.commit()
                    send_json(True, {'id': tid})

                elif action == 'delete_transaction':
                    tid = post_data.get('id') or params.get('id', [''])[0]
                    c.execute("DELETE FROM transactions WHERE id = ?", (tid,))
                    conn.commit()
                    send_json(True, {'id': tid})

                elif action == 'save_vault':
                    v = post_data
                    vid = v.get('id') or f"v-{int(os.times()[4] * 1000)}"
                    c.execute("""
                        INSERT INTO vaults (id, name, amount, color, is_emergency)
                        VALUES (?, ?, ?, ?, ?)
                        ON CONFLICT(id) DO UPDATE SET
                            name=excluded.name,
                            amount=excluded.amount,
                            color=excluded.color,
                            is_emergency=excluded.is_emergency
                    """, (vid, v.get('name'), float(v.get('amount', 0)), v.get('color', 'indigo'), 1 if v.get('isEmergency') else 0))
                    conn.commit()
                    send_json(True, {'id': vid})

                elif action == 'delete_vault':
                    vid = post_data.get('id') or params.get('id', [''])[0]
                    c.execute("DELETE FROM vaults WHERE id = ? AND is_emergency = 0", (vid,))
                    conn.commit()
                    send_json(True, {'id': vid})

                elif action == 'save_goal':
                    g = post_data
                    gid = g.get('id') or f"g-{int(os.times()[4] * 1000)}"
                    c.execute("""
                        INSERT INTO goals (id, name, target, current, deadline, icon)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON CONFLICT(id) DO UPDATE SET
                            name=excluded.name,
                            target=excluded.target,
                            current=excluded.current,
                            deadline=excluded.deadline,
                            icon=excluded.icon
                    """, (gid, g.get('name'), float(g.get('target', 1000)), float(g.get('current', 0)),
                          g.get('deadline'), g.get('icon', 'fa-piggy-bank')))
                    conn.commit()
                    send_json(True, {'id': gid})

                elif action == 'delete_goal':
                    gid = post_data.get('id') or params.get('id', [''])[0]
                    c.execute("DELETE FROM goals WHERE id = ?", (gid,))
                    conn.commit()
                    send_json(True, {'id': gid})

                elif action == 'transfer':
                    ttype = post_data.get('type')
                    tid = post_data.get('id')
                    taction = post_data.get('transferAction', 'deposit')
                    amt = float(post_data.get('amount', 0))

                    if ttype == 'vault':
                        c.execute("SELECT amount FROM vaults WHERE id = ?", (tid,))
                        row = c.fetchone()
                        if not row:
                            send_json(False, error="Forziere non trovato", code=404)
                            return
                        cur = float(row['amount'])
                        new_val = cur + amt if taction == 'deposit' else cur - amt
                        if new_val < 0:
                            send_json(False, error="Fondi insufficienti", code=400)
                            return
                        c.execute("UPDATE vaults SET amount = ? WHERE id = ?", (new_val, tid))
                    elif ttype == 'goal':
                        c.execute("SELECT current FROM goals WHERE id = ?", (tid,))
                        row = c.fetchone()
                        if not row:
                            send_json(False, error="Traguardo non trovato", code=404)
                            return
                        cur = float(row['current'])
                        new_val = cur + amt if taction == 'deposit' else cur - amt
                        if new_val < 0:
                            send_json(False, error="Importo eccede i fondi", code=400)
                            return
                        c.execute("UPDATE goals SET current = ? WHERE id = ?", (new_val, tid))
                    conn.commit()
                    send_json(True, {'id': tid, 'newVal': new_val})

                elif action == 'clear_all':
                    c.execute("DELETE FROM transactions")
                    c.execute("DELETE FROM vaults")
                    c.execute("DELETE FROM goals")
                    c.execute("INSERT INTO vaults (id, name, amount, color, is_emergency) VALUES ('v-emergency', 'Fondo Emergenza', 0.0, 'emerald', 1)")
                    conn.commit()
                    send_json(True, {'message': 'Dati azzerati'})

                elif action == 'import_data':
                    imported = post_data.get('data', post_data)
                    c.execute("DELETE FROM transactions")
                    c.execute("DELETE FROM vaults")
                    c.execute("DELETE FROM goals")
                    for t in imported.get('transactions', []):
                        c.execute("INSERT INTO transactions (id, type, title, amount, category, date, account) VALUES (?, ?, ?, ?, ?, ?, ?)",
                                  (t.get('id'), t.get('type'), t.get('title'), float(t.get('amount', 0)), t.get('category'), t.get('date'), t.get('account', 'Conto Principale')))
                    for v in imported.get('vaults', []):
                        c.execute("INSERT INTO vaults (id, name, amount, color, is_emergency) VALUES (?, ?, ?, ?, ?)",
                                  (v.get('id'), v.get('name'), float(v.get('amount', 0)), v.get('color', 'indigo'), 1 if v.get('isEmergency') else 0))
                    for g in imported.get('goals', []):
                        c.execute("INSERT INTO goals (id, name, target, current, deadline, icon) VALUES (?, ?, ?, ?, ?, ?)",
                                  (g.get('id'), g.get('name'), float(g.get('target', 1000)), float(g.get('current', 0)), g.get('deadline'), g.get('icon', 'fa-piggy-bank')))
                    conn.commit()
                    send_json(True, {'message': 'Import completato'})

                else:
                    send_json(False, error="Azione sconosciuta", code=404)

            except Exception as e:
                conn.rollback()
                send_json(False, error=str(e), code=500)
            finally:
                conn.close()

    server_address = ('', PORT)
    httpd = HTTPServer(server_address, BudgetHandler)
    print("=" * 60)
    print(f"🚀 Alessio Budget Server avviato con successo!")
    print(f"🔗 Apri nel browser: http://localhost:{PORT}")
    print(f"📁 Database SQLite: {DB_FILE}")
    print(f"💡 Premi Ctrl+C per terminare")
    print("=" * 60)
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\n[!] Server terminato.")

if __name__ == "__main__":
    if not run_php():
        print("[i] PHP CLI non trovato nel PATH, avvio tramite runtime Python con SQLite...")
        run_python_server()
