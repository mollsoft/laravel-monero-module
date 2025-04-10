import * as bip39 from 'bip39';
import {
    MoneroWalletConfig,
    MoneroNetworkType,
    MoneroWalletKeys
} from 'monero-ts';

async function main() {
    try {
        const bip39Mnemonic = process.argv[2];
        const bip39Passphrase = process.argv[3] || "";

        if (!bip39Mnemonic || !bip39.validateMnemonic(bip39Mnemonic)) {
            throw new Error("Invalid BIP39 mnemonic provided");
        }

        const seed = bip39.mnemonicToSeedSync(bip39Mnemonic, bip39Passphrase);
        let privateSpendKey = Buffer.from(seed.subarray(0, 32)).toString("hex");

        const config = new MoneroWalletConfig({
            networkType: MoneroNetworkType.MAINNET,
            privateSpendKey,
            proxyToWorker: false
        });

        const wallet = await MoneroWalletKeys.createWallet(config);

        const address = await wallet.getPrimaryAddress();
        const viewKey = await wallet.getPrivateViewKey();
        const spendKey = await wallet.getPrivateSpendKey();
        const mnemonic = await wallet.getSeed();

        console.log(JSON.stringify({
            success: true,
            address,
            spendKey,
            viewKey,
            mnemonic,
        }));
    } catch (error) {
        console.error(JSON.stringify({
            success: false,
            error: error.message || error.toString()
        }));
        process.exit(1);
    }
}

main();
