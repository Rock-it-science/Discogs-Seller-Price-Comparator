import java.io.File;

public class seller {
	File file;
	boolean shippingBool;
	Double shippingCost;
	
	seller(File file, boolean shippingb, Double shippingc){
		this.file = file;
		this.shippingBool = shippingb;
		this.shippingCost = shippingc;
	}
}
