# Bitcoin Address Checker (Go Version)

This is a Go implementation of a Bitcoin address checker that generates random mnemonic phrases, derives Bitcoin addresses, and checks their balances using the blockchain.info API.

## Features

- Generates BIP39 mnemonic phrases (12 words, 128-bit entropy)
- Derives Bitcoin addresses using BIP32 hierarchical deterministic keys
- Checks address balances via blockchain.info API
- Colored console output (green for found addresses, red for errors)
- Logs addresses with balance to `balanced_addresses.txt`
- Rate limiting with 1-second delays between API calls
- **Stops after finding the first address with balance**
- **Tests up to 1 billion addresses**
- Progress reporting every 10,000 addresses

## Requirements

- Go 1.19 or later
- Internet connection for API calls

## Installation

1. Clone or download this code to the `BTC` folder
2. Install dependencies:
   ```bash
   go mod tidy
   ```
3. Build the application:
   ```bash
   go build -o btc-checker
   ```

## Usage

Run the application:
```bash
./btc-checker
```

The program will:
1. Generate up to 1 billion random mnemonic phrases
2. Derive Bitcoin addresses from each phrase
3. Check the balance of each address
4. Display progress every 10,000 addresses
5. **Stop immediately after finding the first address with balance**
6. Log the found address with balance to `balanced_addresses.txt`

## Output

- **Green text**: Addresses with balance found
- **Red text**: Network errors
- **Regular text**: Progress updates every 10,000 addresses

## Files Created

- `balanced_addresses.txt`: Log file containing seeds, addresses, and balances for any addresses with funds

## Dependencies

- `github.com/btcsuite/btcd/btcutil/hdkeychain`: BIP32 hierarchical deterministic keys
- `github.com/btcsuite/btcd/chaincfg`: Bitcoin network parameters
- `github.com/btcsuite/btcd/btcutil`: Bitcoin utilities
- `github.com/fatih/color`: Colored console output
- `github.com/tyler-smith/go-bip39`: BIP39 mnemonic phrase generation

## Note

This tool is for educational purposes. The probability of finding an address with balance is extremely low due to the cryptographic security of Bitcoin addresses.

## Conversion from Python

This Go version is functionally equivalent to the original Python script with the following improvements:
- Better error handling
- More efficient memory usage
- Faster execution
- Static typing
- Single binary deployment