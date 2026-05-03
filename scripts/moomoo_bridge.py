#!/usr/bin/env python3
"""
Moomoo (FutuOpenD) Bridge Script for Firefly III Portfolio Tracker

This script connects to a locally running FutuOpenD daemon, fetches your
portfolio positions, and outputs a JSON file that can be imported into
Firefly III's portfolio tracker.

Requirements:
    pip install futu-api

Usage:
    # Make sure FutuOpenD is running on localhost:11111
    python3 moomoo_bridge.py --output /path/to/positions.json

    # Specify custom host/port
    python3 moomoo_bridge.py --host 192.168.1.100 --port 11111 --output positions.json

    # For Synology NAS cron, chain with the Firefly III import:
    python3 moomoo_bridge.py --output /tmp/moomoo.json && \
    curl -X POST -F 'json_file=@/tmp/moomoo.json' \
         -H 'Authorization: Bearer YOUR_TOKEN' \
         http://localhost:8080/portfolio/import-bridge-json/1

Setup on Synology NAS:
    1. Install Python 3 package from Package Center
    2. SSH into your NAS: ssh admin@your-nas-ip
    3. pip install futu-api
    4. Download and run FutuOpenD: https://openapi.futunn.com/futu-api-doc/en/intro/intro.html
    5. Add this script to Task Scheduler (Control Panel > Task Scheduler)
"""

import argparse
import json
import sys
from datetime import datetime


def fetch_positions(host: str, port: int, market: str, password: str | None = None):
    """Fetch positions from FutuOpenD."""
    try:
        from futu import (
            OpenSecureTradeContext,
            TrdEnv,
            TrdMarket,
            SecurityFirm,
            RET_OK,
        )
    except ImportError:
        print("Error: futu-api not installed. Run: pip install futu-api", file=sys.stderr)
        sys.exit(1)

    market_map = {
        "US": TrdMarket.US,
        "HK": TrdMarket.HK,
        "SG": TrdMarket.SG,
        "CN": TrdMarket.CN,
        "AU": TrdMarket.AU,
        "JP": TrdMarket.JP,
    }

    trd_market = market_map.get(market.upper(), TrdMarket.US)

    try:
        trd_ctx = OpenSecureTradeContext(
            filter_trdmarket=trd_market,
            host=host,
            port=port,
            security_firm=SecurityFirm.FUTUSECURITIES,
        )
    except Exception as e:
        print(f"Error connecting to FutuOpenD at {host}:{port}: {e}", file=sys.stderr)
        sys.exit(1)

    try:
        if password:
            ret, data = trd_ctx.unlock_trade(password=password)
            if ret != RET_OK:
                print(f"Warning: Could not unlock trade: {data}", file=sys.stderr)

        # Fetch positions
        ret, positions_df = trd_ctx.position_list_query()
        if ret != RET_OK:
            print(f"Error fetching positions: {positions_df}", file=sys.stderr)
            return {"positions": [], "error": str(positions_df)}

        positions = []
        for _, row in positions_df.iterrows():
            positions.append({
                "code": row.get("code", ""),
                "stock_name": row.get("stock_name", ""),
                "qty": float(row.get("qty", 0)),
                "cost_price": float(row.get("cost_price", 0)),
                "market_val": float(row.get("market_val", 0)),
                "pl_val": float(row.get("pl_val", 0)),
                "pl_ratio": float(row.get("pl_ratio", 0)),
                "currency": row.get("currency", "USD"),
                "nominal_price": float(row.get("nominal_price", 0)),
                "last_price": float(row.get("last_price", 0)),
            })

        # Fetch account funds (cash balance)
        ret, funds_df = trd_ctx.accinfo_query()
        funds = {}
        if ret == RET_OK and funds_df is not None:
            for _, row in funds_df.iterrows():
                funds = {
                    "total_assets": float(row.get("total_assets", 0)),
                    "cash": float(row.get("cash", 0)),
                    "market_val": float(row.get("market_val", 0)),
                    "currency": row.get("currency", "USD"),
                }

        return {
            "positions": positions,
            "funds": funds,
            "market": market.upper(),
            "fetched_at": datetime.now().isoformat(),
        }

    finally:
        trd_ctx.close()


def main():
    parser = argparse.ArgumentParser(
        description="Fetch Moomoo portfolio data via FutuOpenD and output JSON"
    )
    parser.add_argument("--host", default="127.0.0.1", help="FutuOpenD host (default: 127.0.0.1)")
    parser.add_argument("--port", type=int, default=11111, help="FutuOpenD port (default: 11111)")
    parser.add_argument("--market", default="US", help="Trading market: US, HK, SG, CN, AU, JP (default: US)")
    parser.add_argument("--password", default=None, help="Trade unlock password (optional, for position details)")
    parser.add_argument("--output", "-o", default="moomoo_positions.json", help="Output JSON file path")

    args = parser.parse_args()

    print(f"Connecting to FutuOpenD at {args.host}:{args.port} (market: {args.market})...")

    result = fetch_positions(args.host, args.port, args.market, args.password)

    with open(args.output, "w") as f:
        json.dump(result, f, indent=2)

    num_positions = len(result.get("positions", []))
    print(f"Exported {num_positions} position(s) to {args.output}")

    if num_positions > 0:
        total_value = sum(p["market_val"] for p in result["positions"])
        total_pnl = sum(p["pl_val"] for p in result["positions"])
        print(f"Total market value: {total_value:,.2f}")
        print(f"Total P&L: {total_pnl:+,.2f}")


if __name__ == "__main__":
    main()
