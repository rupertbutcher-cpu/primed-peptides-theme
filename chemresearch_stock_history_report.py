"""
Builds a local HTML report of the ChemResearch daily stock-history log
(chemresearch_stock_history.jsonl, one row per product per day, written by
chemresearch_monitor.py's daily 7am run) - streaks and in/out-of-stock
frequency per product, so Rupert can see which products are unreliable
enough to plan ordering around proactively.

Needs a few weeks of history to be genuinely useful - with only a handful
of days logged the streak/frequency numbers are just "since we started
watching", not necessarily normal behaviour for that product.

Run any time: python chemresearch_stock_history_report.py
Writes chemresearch_stock_history_report.html next to this script - double
click to open in a browser. Safe to re-run any time, always overwrites with
the latest data, never touches the source JSONL log.
"""
import datetime
import json
import os

HERE = os.path.dirname(os.path.abspath(__file__))
HISTORY_FILE = os.path.join(HERE, "chemresearch_stock_history.jsonl")
OUT_FILE = os.path.join(HERE, "chemresearch_stock_history_report.html")


def load_history():
    by_product = {}
    with open(HISTORY_FILE, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            row = json.loads(line)
            by_product.setdefault(row["name"], []).append(row)
    for rows in by_product.values():
        rows.sort(key=lambda r: r["date"])
    return by_product


def summarize(rows):
    days_tracked = len(rows)
    days_out = sum(1 for r in rows if not r["inStock"])
    pct_out = round(100 * days_out / days_tracked) if days_tracked else 0

    # Current streak: consecutive days at the end in the same state as the latest row.
    current_state = rows[-1]["inStock"]
    streak = 0
    for r in reversed(rows):
        if r["inStock"] != current_state:
            break
        streak += 1

    # Number of times it flipped OUT of stock (a real stockout event, not just "still out").
    stockout_events = 0
    for prev, cur in zip(rows, rows[1:]):
        if prev["inStock"] and not cur["inStock"]:
            stockout_events += 1

    return {
        "days_tracked": days_tracked,
        "days_out": days_out,
        "pct_out": pct_out,
        "current_state": current_state,
        "streak": streak,
        "stockout_events": stockout_events,
        "first_date": rows[0]["date"],
        "last_date": rows[-1]["date"],
        "latest_qty": rows[-1]["qty"],
    }


def dot_strip(rows):
    cells = []
    for r in rows:
        cls = "in" if r["inStock"] else "out"
        title = f"{r['date']}: {'in stock (' + str(r['qty']) + ')' if r['inStock'] else 'OUT of stock'}"
        cells.append(f'<span class="dot dot-{cls}" title="{title}"></span>')
    return "".join(cells)


def build_html(by_product):
    rows_html = []
    summaries = []
    for name, rows in by_product.items():
        s = summarize(rows)
        s["name"] = name
        s["rows"] = rows
        summaries.append(s)

    # Worst-first: most days out of stock, then most stockout events, then name.
    summaries.sort(key=lambda s: (-s["pct_out"], -s["stockout_events"], s["name"]))

    for s in summaries:
        status_pill = (
            '<span class="pill out">Out of stock</span>' if not s["current_state"]
            else '<span class="pill in">In stock</span>'
        )
        rows_html.append(f"""
        <tr>
          <td class="name">{s['name']}</td>
          <td>{status_pill}</td>
          <td class="num">{s['streak']}d</td>
          <td class="num">{s['stockout_events']}</td>
          <td class="num">{s['pct_out']}%</td>
          <td class="num">{s['latest_qty'] if s['latest_qty'] is not None else '-'}</td>
          <td class="dots">{dot_strip(s['rows'])}</td>
        </tr>""")

    days_tracked = max((s["days_tracked"] for s in summaries), default=0)
    first_date = min((s["first_date"] for s in summaries), default="-")
    last_date = max((s["last_date"] for s in summaries), default="-")
    generated = datetime.datetime.now().strftime("%Y-%m-%d %H:%M")

    warn = ""
    if days_tracked < 14:
        warn = (f'<div class="warn">Only {days_tracked} day(s) of history logged so far '
                f'(started {first_date}) - streak and frequency numbers below will firm up '
                f'as more days come in. Check back in a few weeks for the real pattern.</div>')

    return f"""<!doctype html>
<html><head><meta charset="utf-8">
<title>ChemResearch Stock History</title>
<style>
  :root {{
    --bg:#f6f5f2; --fg:#2a2a28; --muted:#77746c; --card:#fff; --border:#ece9e2;
    --head-bg:#efece4; --head-fg:#4a4740; --hover:#fbfaf7;
    --warn-bg:#fff4e0; --warn-border:#e8c27a; --warn-fg:#7a5a10;
    --in-bg:#e2f3e8; --in-fg:#1e6b3c; --out-bg:#fbe4e1; --out-fg:#a5301f;
    --dot-in:#3a9d5c; --dot-out:#c0392b; --shadow:rgba(0,0,0,0.08);
  }}
  @media (prefers-color-scheme: dark) {{
    :root {{
      --bg:#1c1b18; --fg:#e8e6df; --muted:#9a9689; --card:#252420; --border:#37352e;
      --head-bg:#2d2b26; --head-fg:#c9c5b8; --hover:#2a2924;
      --warn-bg:#3a2e12; --warn-border:#6b5220; --warn-fg:#e8c27a;
      --in-bg:#1c3626; --in-fg:#6fcf97; --out-bg:#3d1f1a; --out-fg:#e8897a;
      --dot-in:#4ab86e; --dot-out:#d9564a; --shadow:rgba(0,0,0,0.4);
    }}
  }}
  :root[data-theme="dark"] {{
    --bg:#1c1b18; --fg:#e8e6df; --muted:#9a9689; --card:#252420; --border:#37352e;
    --head-bg:#2d2b26; --head-fg:#c9c5b8; --hover:#2a2924;
    --warn-bg:#3a2e12; --warn-border:#6b5220; --warn-fg:#e8c27a;
    --in-bg:#1c3626; --in-fg:#6fcf97; --out-bg:#3d1f1a; --out-fg:#e8897a;
    --dot-in:#4ab86e; --dot-out:#d9564a; --shadow:rgba(0,0,0,0.4);
  }}
  :root[data-theme="light"] {{
    --bg:#f6f5f2; --fg:#2a2a28; --muted:#77746c; --card:#fff; --border:#ece9e2;
    --head-bg:#efece4; --head-fg:#4a4740; --hover:#fbfaf7;
    --warn-bg:#fff4e0; --warn-border:#e8c27a; --warn-fg:#7a5a10;
    --in-bg:#e2f3e8; --in-fg:#1e6b3c; --out-bg:#fbe4e1; --out-fg:#a5301f;
    --dot-in:#3a9d5c; --dot-out:#c0392b; --shadow:rgba(0,0,0,0.08);
  }}
  body {{ font-family: -apple-system, Segoe UI, Arial, sans-serif; background:var(--bg); color:var(--fg); margin:0; padding:32px; }}
  h1 {{ font-size: 22px; margin: 0 0 4px; text-wrap: balance; }}
  .meta {{ color:var(--muted); font-size: 13px; margin-bottom: 20px; }}
  .warn {{ background:var(--warn-bg); border:1px solid var(--warn-border); color:var(--warn-fg); padding:10px 14px; border-radius:6px; font-size:13px; margin-bottom:20px; max-width:720px; }}
  .table-wrap {{ overflow-x: auto; border-radius:8px; box-shadow:0 1px 3px var(--shadow); }}
  table {{ border-collapse: collapse; width: 100%; background:var(--card); }}
  th, td {{ padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: 13px; text-align:left; }}
  th {{ background:var(--head-bg); font-weight:600; color:var(--head-fg); text-transform:uppercase; font-size:11px; letter-spacing:0.04em; }}
  td.num {{ text-align:right; font-variant-numeric: tabular-nums; }}
  td.name {{ max-width: 320px; }}
  .pill {{ display:inline-block; padding:2px 9px; border-radius:99px; font-size:11px; font-weight:600; }}
  .pill.in {{ background:var(--in-bg); color:var(--in-fg); }}
  .pill.out {{ background:var(--out-bg); color:var(--out-fg); }}
  .dots {{ white-space: nowrap; }}
  .dot {{ display:inline-block; width:8px; height:8px; border-radius:2px; margin-right:2px; }}
  .dot-in {{ background:var(--dot-in); }}
  .dot-out {{ background:var(--dot-out); }}
  tbody tr:hover {{ background:var(--hover); }}
</style></head>
<body>
  <h1>ChemResearch Stock History — Primed Peptides</h1>
  <div class="meta">{first_date} to {last_date} ({days_tracked} day{'s' if days_tracked != 1 else ''} tracked) · generated {generated}</div>
  {warn}
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>Product</th><th>Now</th><th>Current streak</th><th>Times gone OOS</th><th>% days OOS</th><th>Latest qty</th><th>History</th>
    </tr></thead>
    <tbody>
      {''.join(rows_html)}
    </tbody>
  </table>
  </div>
</body></html>"""


def main():
    by_product = load_history()
    if not by_product:
        print("No history yet - chemresearch_stock_history.jsonl is empty or missing.")
        return
    html = build_html(by_product)
    with open(OUT_FILE, "w", encoding="utf-8") as f:
        f.write(html)
    print(f"Wrote {OUT_FILE} ({len(by_product)} products)")


if __name__ == "__main__":
    main()
