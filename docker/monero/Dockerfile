FROM hvalev/monero-node:latest as base

COPY monero-wallet-rpc.conf /monero-wallet-rpc.conf

CMD /monerod --config-file /monerod.conf --non-interactive & /monero-wallet-rpc --config-file /monero-wallet-rpc.conf --non-interactive
