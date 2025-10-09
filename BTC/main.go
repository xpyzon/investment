package main

import (
	"bufio"
	"fmt"
	"io"
	"net/http"
	"os"
	"strconv"
	"strings"
	"time"

	"github.com/btcsuite/btcd/btcutil/hdkeychain"
	"github.com/btcsuite/btcd/chaincfg"
	"github.com/btcsuite/btcd/btcutil"
	"github.com/fatih/color"
	"github.com/tyler-smith/go-bip39"
)

const (
	timeout   = 10 * time.Second
	sleepTime = 5 * time.Second
)

// Colors for output
var (
	green = color.New(color.FgGreen)
	red   = color.New(color.FgRed)
)

func main() {
	// Open log file for addresses with balance
	logFile, err := os.OpenFile("balanced_addresses.txt", os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0644)
	if err != nil {
		fmt.Printf("Error opening log file: %v\n", err)
		return
	}
	defer logFile.Close()

	writer := bufio.NewWriter(logFile)
	defer writer.Flush()

	fmt.Println("Starting Bitcoin address checker...")
	fmt.Println("Generating and checking 100,000 addresses...")

	for i := 0; i < 100000; i++ {
		// Generate mnemonic (128 bits = 12 words)
		entropy, err := bip39.NewEntropy(128)
		if err != nil {
			fmt.Printf("Error generating entropy: %v\n", err)
			continue
		}

		mnemonic, err := bip39.NewMnemonic(entropy)
		if err != nil {
			fmt.Printf("Error generating mnemonic: %v\n", err)
			continue
		}

		// Generate seed from mnemonic
		seed := bip39.NewSeed(mnemonic, "")

		// Create master key
		masterKey, err := hdkeychain.NewMaster(seed, &chaincfg.MainNetParams)
		if err != nil {
			fmt.Printf("Error creating master key: %v\n", err)
			continue
		}

		// Derive path m/0/0 (similar to Python's ChildKey(0).ChildKey(0))
		childKey0, err := masterKey.Derive(0)
		if err != nil {
			fmt.Printf("Error deriving child key 0: %v\n", err)
			continue
		}

		childKey00, err := childKey0.Derive(0)
		if err != nil {
			fmt.Printf("Error deriving child key 0/0: %v\n", err)
			continue
		}

		// Get the public key and create address
		pubKey, err := childKey00.ECPubKey()
		if err != nil {
			fmt.Printf("Error getting public key: %v\n", err)
			continue
		}

		// Create P2PKH address (legacy Bitcoin address)
		pubKeyHash := btcutil.Hash160(pubKey.SerializeCompressed())
		address, err := btcutil.NewAddressPubKeyHash(pubKeyHash, &chaincfg.MainNetParams)
		if err != nil {
			fmt.Printf("Error creating address: %v\n", err)
			continue
		}

		addressStr := address.EncodeAddress()

		// Check balance via blockchain.info API
		balance, err := checkBalance(addressStr)
		if err != nil {
			red.Printf("Network error for %s: %v\n", addressStr, err)
			continue
		}

		if balance > 0 {
			// Found address with balance
			green.Printf("FOUND: %s -> %s has %d satoshis\n", mnemonic, addressStr, balance)
			
			// Log to file
			logEntry := fmt.Sprintf("Seed: %s\nAddress: %s\nBalance: %d satoshis\n\n", 
				mnemonic, addressStr, balance)
			writer.WriteString(logEntry)
			writer.Flush() // Ensure immediate writing
		} else {
			fmt.Printf("SKIPPING: %s -> %s\n", mnemonic, addressStr)
		}

		// Sleep between requests to avoid rate limiting
		time.Sleep(sleepTime)
	}

	fmt.Println("Finished checking addresses.")
}

// checkBalance queries the blockchain.info API for address balance
func checkBalance(address string) (int64, error) {
	url := fmt.Sprintf("https://blockchain.info/q/addressbalance/%s", address)
	
	client := &http.Client{
		Timeout: timeout,
	}

	resp, err := client.Get(url)
	if err != nil {
		return 0, err
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return 0, err
	}

	balanceStr := strings.TrimSpace(string(body))
	balance, err := strconv.ParseInt(balanceStr, 10, 64)
	if err != nil {
		// If parsing fails, assume balance is 0
		return 0, nil
	}

	return balance, nil
}