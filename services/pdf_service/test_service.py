#!/usr/bin/env python3
"""
Simple test script for PDF service functionality
Tests the core PDF extraction and rendering without Laravel integration
"""
import requests
import json
import os
import sys

# Configuration
PDF_SERVICE_URL = "http://127.0.0.1:8001"
TEST_PDF_PATH = "../../contract_fixed_v1.pdf"

def test_service_health():
    """Test if the PDF service is running"""
    try:
        response = requests.get(f"{PDF_SERVICE_URL}/")
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Service is running: {data.get('message', 'OK')}")
            return True
        else:
            print(f"❌ Service returned status code: {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ Cannot connect to PDF service. Is it running on port 8001?")
        return False
    except Exception as e:
        print(f"❌ Error testing service: {e}")
        return False

def test_pdf_extraction():
    """Test PDF text extraction"""
    if not os.path.exists(TEST_PDF_PATH):
        print(f"❌ Test PDF not found: {TEST_PDF_PATH}")
        return False
    
    try:
        with open(TEST_PDF_PATH, 'rb') as f:
            files = {'file': ('test.pdf', f, 'application/pdf')}
            response = requests.post(f"{PDF_SERVICE_URL}/extract_positions", files=files)
        
        if response.status_code == 200:
            data = response.json()
            positions = data.get('positions', [])
            print(f"✅ Successfully extracted {len(positions)} text positions")
            
            # Show sample of extracted text
            sample_texts = [pos['text'][:50] + "..." if len(pos['text']) > 50 
                          else pos['text'] for pos in positions[:5]]
            print("   Sample extracted text:")
            for text in sample_texts:
                if text.strip():
                    print(f"   - {text}")
            return True
        else:
            print(f"❌ Extraction failed with status: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Error during extraction: {e}")
        return False

def test_overlay_rendering():
    """Test PDF overlay rendering"""
    try:
        # Sample positions for testing
        test_positions = {
            "positions": [
                {
                    "page": 1,
                    "text": "نص تجريبي عربي",
                    "bbox": [100, 100, 200, 120]
                },
                {
                    "page": 1,
                    "text": "Test English text",
                    "bbox": [100, 150, 200, 170]
                }
            ]
        }
        
        response = requests.post(
            f"{PDF_SERVICE_URL}/render_overlay",
            json=test_positions,
            headers={'Content-Type': 'application/json'}
        )
        
        if response.status_code == 200:
            print("✅ Successfully rendered overlay PDF")
            print(f"   Generated PDF size: {len(response.content)} bytes")
            
            # Save test output
            with open("test_overlay.pdf", "wb") as f:
                f.write(response.content)
            print("   Saved as test_overlay.pdf")
            return True
        else:
            print(f"❌ Overlay rendering failed with status: {response.status_code}")
            print(f"   Response: {response.text}")
            return False
            
    except Exception as e:
        print(f"❌ Error during overlay rendering: {e}")
        return False

def main():
    """Run all tests"""
    print("🔄 Testing ContractSama PDF Service...")
    print("=" * 50)
    
    # Check if service is running
    if not test_service_health():
        print("\n💡 To start the PDF service, run:")
        print("   cd services/pdf_service")
        print("   python main.py")
        return False
    
    print()
    
    # Test extraction
    print("🔄 Testing PDF text extraction...")
    extraction_ok = test_pdf_extraction()
    print()
    
    # Test overlay rendering
    print("🔄 Testing overlay rendering...")
    rendering_ok = test_overlay_rendering()
    print()
    
    # Summary
    print("=" * 50)
    if extraction_ok and rendering_ok:
        print("🎉 All tests passed! PDF service is working correctly.")
        print("\n📝 Next steps:")
        print("   1. Install PHP and Composer")
        print("   2. Run: cd php_app && composer install")
        print("   3. Run: php artisan serve")
        print("   4. Test Laravel + PDF service integration")
    else:
        print("❌ Some tests failed. Check the service implementation.")
    
    return extraction_ok and rendering_ok

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)